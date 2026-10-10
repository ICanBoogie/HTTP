<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use InvalidArgumentException;
use RuntimeException;
use LogicException;
use SplFileInfo;

use function base64_encode;
use function fclose;
use function finfo_file;
use function finfo_open;
use function fopen;
use function function_exists;
use function hash_file;
use function preg_match_all;
use function sprintf;
use function str_starts_with;
use function stream_copy_to_stream;
use function substr;
use function trim;

use const FILEINFO_MIME_TYPE;

/**
 * Representation of an HTTP response delivering a file.
 */
class FileResponse extends Response
{
    /**
     * Specifies the `ETag` header field of the response.
     * If it is not defined, a validator derived from the modification time and the size of the
     * file is used instead, see {@see make_etag()}.
     */
    public const string OPTION_ETAG = 'etag';

    /**
     * Specifies the expiration date as a {@see \DateTimeInterface} instance or a relative date
     * such as "+3 month", which maps to the `Expires` header field. Unless `Cache-Control` is
     * defined, its `max-age` directive is computed from the current time. If it is not
     * defined {@see DEFAULT_EXPIRES} is used instead.
     */
    public const string OPTION_EXPIRES = 'expires';

    /**
     * Specifies the filename of the file and forces download. The following headers are updated:
     * `Content-Transfer-Encoding`, `Content-Description`, and `Content-Disposition`.
     */
    public const string OPTION_FILENAME = 'filename';

    /**
     * Specifies the MIME of the file, which maps to the `Content-Type` header field.
     * If it is not defined, the MIME is guessed using `finfo::file()`.
     */
    public const string OPTION_MIME = 'mime';

    public const string DEFAULT_EXPIRES = '+1 month';
    public const string DEFAULT_MIME = 'application/octet-stream';

    /**
     * Hashes a file using SHA-384.
     *
     * The hash can be used with {@see OPTION_ETAG}, wrapped in double quotes, when a validator
     * derived from the content is preferred to the default one. Note that the file is read
     * entirely.
     *
     * @return string A base64 string
     */
    public static function hash_file(string $pathname): string
    {
        return base64_encode(hash_file('sha384', $pathname, true));
    }

    public readonly SplFileInfo $file;

    /**
     * @param array<string, mixed> $options
     * @param Headers|array<string, mixed> $headers
     */
    public function __construct(
        string|SplFileInfo $file,
        private readonly Request $request,
        array $options = [],
        Headers|array $headers = [],
    ) {
        if (!$headers instanceof Headers) {
            $headers = new Headers($headers);
        }

        $this->file = $this->ensure_file_info($file);
        $this->apply_options($options, $headers);
        $this->ensure_content_type($this->file, $headers);

        parent::__construct(function () {
            if (!$this->status->is_successful) {
                return;
            }

            $this->send_file($this->file);
        }, ResponseStatus::STATUS_OK, $headers);
    }

    /**
     * Ensures the provided file is a {@see \SplFileInfo} instance.
     *
     * @throws LogicException if the file is a directory or doesn't exist.
     */
    private function ensure_file_info(mixed $file): SplFileInfo
    {
        $file = $file instanceof SplFileInfo ? $file : new SplFileInfo($file);

        if ($file->isDir()) {
            throw new LogicException("Expected file, got directory: $file");
        }

        if (!$file->isFile()) {
            throw new LogicException("File does not exist: $file");
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function apply_options(array $options, Headers $headers): void
    {
        foreach ($options as $option => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            switch ($option) {
                case self::OPTION_ETAG:
                    if ($headers->etag) {
                        throw new InvalidArgumentException("Can only use one of OPTION_ETAG, HEADER_ETAG.");
                    }

                    $headers->etag = $value;
                    break;

                case self::OPTION_EXPIRES:
                    $headers->expires = $value;
                    break;

                case self::OPTION_FILENAME:
                    $headers['Content-Transfer-Encoding'] = 'binary';
                    $headers['Content-Description'] = 'File Transfer';
                    $headers->content_disposition->type = 'attachment';
                    $headers->content_disposition->filename = $value === true ? $this->file->getFilename() : $value;
                    break;

                case self::OPTION_MIME:
                    $headers->content_type = $value;
                    break;

                default:
                    throw new InvalidArgumentException("Unsupported option: $option.");
            }
        }

        $headers->etag ??= $this->make_etag();
    }

    /**
     * If the content type is empty in the headers, the method tries to get it from
     * the file, if it fails {@see DEFAULT_MIME} is used as fallback.
     */
    private function ensure_content_type(SplFileInfo $file, Headers $headers): void
    {
        if ($headers->content_type->value) {
            return;
        }

        $mime = null;

        if (function_exists('finfo_file') && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $file->getPathname()) : null;
        }

        $headers->content_type = $mime ?: self::DEFAULT_MIME;
    }

    /**
     * Changes the status according to the request:
     *
     * - {@see ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE} if the range cannot be
     *   satisfied.
     * - {@see ResponseStatus::STATUS_PARTIAL_CONTENT} if the range is a part of the file.
     * - {@see ResponseStatus::STATUS_NOT_MODIFIED} if the method is `GET` or `HEAD`, the request's
     *   `Cache-Control` doesn't have `no-cache`, and {@see $is_modified} is `false`.
     */
    public function __invoke(): void
    {
        $range = $this->range;

        if ($range) {
            if (!$range->is_satisfiable) {
                $this->status = ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE;
            } elseif (!$range->is_total) {
                $this->status = ResponseStatus::STATUS_PARTIAL_CONTENT;
            }
        }

        $method = $this->request->method;

        if (
            ($method->is_get() || $method->is_head())
            && $this->request->headers->cache_control->cacheable !== 'no-cache'
            && !$this->is_modified
        ) {
            $this->status = ResponseStatus::STATUS_NOT_MODIFIED;
        }

        parent::__invoke();
    }

    /**
     * If `Cache-Control` is not defined, the response is made cacheable by the client only:
     *
     * - `Cache-Control`: is set to `private` with `max-age` computed from {@see $expires}.
     * - `Expires`: is set to {@see $expires}, "+1 month" by default.
     *
     * A defined `Cache-Control` is left untouched, so is `Expires`. Use `public` explicitly for
     * files that may be stored by shared caches.
     *
     * If the status code is {@see ResponseStatus::STATUS_NOT_MODIFIED}, `Content-Length` is
     * unset.
     *
     * If the status code is {@see ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE},
     * `Content-Range` is set to the size of the file and `Content-Length` to 0, since no body is
     * sent.
     *
     * Otherwise, `Last-Modified` and `Content-Length` are set, and `Content-Range` if the status
     * code is {@see ResponseStatus::STATUS_PARTIAL_CONTENT}.
     *
     * @inheritdoc
     */
    protected function finalize(Headers &$headers, &$body): void
    {
        parent::finalize($headers, $body);

        $this->finalize_cache_control($headers);

        match ($this->status->code) {
            ResponseStatus::STATUS_NOT_MODIFIED => $this->finalize_for_not_modified($headers),
            ResponseStatus::STATUS_PARTIAL_CONTENT => $this->finalize_for_partial_content($headers),
            ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE => $this->finalize_for_range_not_satisfiable($headers),
            default => $this->finalize_for_other($headers),
        };
    }

    /**
     * Defaults to a `private` response if `Cache-Control` is not defined.
     *
     * Shared caches must not store a file that could have been served after an authorization
     * check, so `public` must be opted into.
     */
    private function finalize_cache_control(Headers $headers): void
    {
        if ((string) $headers->cache_control !== '') {
            return;
        }

        $expires = $this->expires;

        $headers->expires = $expires;
        $headers->cache_control->cacheable = 'private';
        $headers->cache_control->max_age = $expires->timestamp - time();
    }

    /**
     * Finalizes the response for {@see Status::NOT_MODIFIED}.
     */
    private function finalize_for_not_modified(Headers &$headers): void
    {
        $headers->content_length = null;
    }

    /**
     * Finalizes the response for {@see Status::REQUESTED_RANGE_NOT_SATISFIABLE}.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#name-416-range-not-satisfiable
     */
    private function finalize_for_range_not_satisfiable(Headers $headers): void
    {
        $headers['Content-Range'] = 'bytes */' . $this->file->getSize();
        $headers->content_length = 0;
    }

    /**
     * Finalizes the response for {@see Status::PARTIAL_CONTENT}.
     */
    private function finalize_for_partial_content(Headers &$headers): void
    {
        $range = $this->range;

        $headers->last_modified = $this->modified_time;
        $headers['Content-Range'] = (string)$range;
        $headers->content_length = $range->length;
    }

    /**
     * Finalizes the response for status other than {@see Status::NOT_MODIFIED},
     * {@see Status::PARTIAL_CONTENT}, or {@see Status::REQUESTED_RANGE_NOT_SATISFIABLE}.
     */
    private function finalize_for_other(Headers &$headers): void
    {
        $headers->last_modified = $this->modified_time;

        if (!$headers[Headers::HEADER_ACCEPT_RANGES]) {
            $request = $this->request;

            $headers[Headers::HEADER_ACCEPT_RANGES] = $request->method->is_get()
            || $request->method->is_head() ? 'bytes' : 'none';
        }

        $headers->content_length = $this->file->getSize();
    }

    /**
     * Sends the file.
     *
     * @codeCoverageIgnore
     */
    protected function send_file(SplFileInfo $file): void
    {
        [ $max_length, $offset ] = $this->resolve_max_length_and_offset();

        $source = fopen($file->getPathname(), 'rb');

        if ($source === false) {
            throw new RuntimeException("Unable to open file: {$file->getPathname()}");
        }

        $out = fopen('php://output', 'wb');

        stream_copy_to_stream($source, $out, $max_length, $offset);

        fclose($out);
        fclose($source);
    }

    /**
     * Resolves `max_length` and `offset` parameters for stream copy.
     *
     * @return array{ 0: int, 1: int }
     */
    private function resolve_max_length_and_offset(): array
    {
        $range = $this->range;

        if ($range && $range->max_length) {
            return [ $range->max_length, $range->offset ];
        }

        return [ -1, 0 ];
    }

    /**
     * Returns a validator derived from the modification time and the size of the file.
     *
     * Unlike a hash of the content, it doesn't require reading the file, which matters because
     * the validator is computed for every response, including those that end up as 304.
     */
    private function make_etag(): string
    {
        return sprintf('"%x-%x"', $this->file->getMTime(), $this->file->getSize());
    }

    /**
     * Whether `If-None-Match` matches an entity tag, using the weak comparison.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#name-if-none-match
     */
    private static function if_none_match_matches(string $if_none_match, string $etag): bool
    {
        if (trim($if_none_match) === '*') {
            return true;
        }

        $opaque_tag = self::opaque_tag($etag);

        // Unquoted tags are not valid, but they are matched to support unquoted OPTION_ETAG values.
        preg_match_all('/(?:W\/)?(?:"[^"]*"|[^,\s]+)/', $if_none_match, $matches);

        foreach ($matches[0] as $candidate) {
            if (self::opaque_tag($candidate) === $opaque_tag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the weakness indicator of an entity tag.
     */
    private static function opaque_tag(string $etag): string
    {
        return str_starts_with($etag, 'W/') ? substr($etag, 2) : $etag;
    }

    /**
     * If the date returned by the parent is empty, the method returns a date created from
     * {@see DEFAULT_EXPIRES}.
     */
    public Headers\Date|null $expires {
        get {
            // @phpstan-ignore-next-line // false positive, this is a valid way to call a parent getter
            $expires = parent::$expires::get();

            if (!$expires->is_empty) {
                return $expires;
            }

            return Headers\Date::from(self::DEFAULT_EXPIRES);
        }
    }

    /**
     * The timestamp at which the file was last modified.
     */
    public int $modified_time
        {
            get => $this->file->getMTime();
        }

    /**
     * Whether the file has been modified since the last response.
     *
     * If the `If-None-Match` request header is defined, the file is considered modified if none of
     * its entity tags match `ETag`; `If-Modified-Since` is then ignored.
     *
     * Otherwise, the file is considered modified if one of the following conditions is met:
     *
     * - The `If-Modified-Since` request header is empty.
     * - The `If-Modified-Since` value is inferior to `$modified_time`.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#name-evaluation
     */
    public bool $is_modified
        {
            get {
                $headers = $this->request->headers;
                $if_none_match = (string) $headers[Headers::HEADER_IF_NONE_MATCH];

                if ($if_none_match !== '') {
                    return !self::if_none_match_matches($if_none_match, (string) $this->headers->etag);
                }

                $if_modified_since = $headers->if_modified_since;

                return $if_modified_since->is_empty || $if_modified_since->timestamp < $this->modified_time;
            }
        }

    public ?RequestRange $range {
        get {
            return $this->range ??= RequestRange::from(
                $this->request->headers,
                $this->file->getSize(),
                $this->headers->etag,
                $this->modified_time,
            );
        }
    }
}
