<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use ICanBoogie\DateTime;
use InvalidArgumentException;
use LogicException;
use SplFileInfo;

use function array_filter;
use function base64_encode;
use function fclose;
use function finfo_file;
use function finfo_open;
use function fopen;
use function function_exists;
use function hash_file;
use function stream_copy_to_stream;

use const FILEINFO_MIME_TYPE;

/**
 * Representation of an HTTP response delivering a file.
 *
 * @property-read SplFileInfo $file
 * @property-read int $modified_time
 * @property-read RequestRange $range
 * @property-read bool $is_modified
 */
class FileResponse extends Response
{
    /**
     * Specifies the `ETag` header field of the response. If it is not defined the
     * SHA-384 of the file is used instead.
     */
    public const OPTION_ETAG = 'etag';

    /**
     * Specifies the expiration date as a {@link DateTime} instance or a relative date
     * such as "+3 month", which maps to the `Expires` header field. The `max-age` directive of
     * the `Cache-Control` header field is computed from the current time. If it is not
     * defined {@link DEFAULT_EXPIRES} is used instead.
     */
    public const OPTION_EXPIRES = 'expires';

    /**
     * Specifies the filename of the file and forces download. The following header are updated:
     * `Content-Transfer-Encoding`, `Content-Description`, and `Content-Dispositon`.
     */
    public const OPTION_FILENAME = 'filename';

    /**
     * Specifies the MIME of the file, which maps to the `Content-Type` header field. If it is
     * not defined the MIME is guessed using `finfo::file()`.
     */
    public const OPTION_MIME = 'mime';

    public const DEFAULT_EXPIRES = '+1 month';
    public const DEFAULT_MIME = 'application/octet-stream';

    /**
     * Hashes a file using SHA-348.
     *
     * @return string A base64 string
     */
    public static function hash_file(string $pathname): string
    {
        return base64_encode(hash_file('sha384', $pathname, true));
    }

    private SplFileInfo $file;

    protected function get_file(): SplFileInfo
    {
        return $this->file;
    }

    /**
     * @param array<string, mixed> $options
     * @param Headers|array<string, mixed> $headers
     */
    public function __construct(
        string|SplFileInfo $file,
        private readonly Request $request,
        array $options = [],
        Headers|array $headers = []
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
        }, Status::OK, $headers);
    }

    /**
     * Ensures the provided file is a {@link \SplFileInfo} instance.
     *
     * @throws LogicException if the file is a directory, or does not exist.
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
        foreach (array_filter($options) as $option => $value) {
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
            }
        }

        $headers->etag ??= $this->make_etag();
    }

    /**
     * If the content type is empty in the headers the method tries to obtain it from
     * the file, if it fails {@link DEFAULT_MIME} is used as fallback.
     */
    private function ensure_content_type(SplFileInfo $file, Headers $headers): void
    {
        if ($headers->content_type->value) {
            return;
        }

        $mime = null;

        if (function_exists('finfo_file')) {
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        }

        $headers->content_type = $mime ?? self::DEFAULT_MIME;
    }

    /**
     * Changes the status to `Status::NOT_MODIFIED` if the request's Cache-Control has
     * 'no-cache' and `is_modified` is false.
     */
    public function __invoke(): void
    {
        $range = $this->range;

        if ($range) {
            if (!$range->is_satisfiable) {
                $this->status = Status::REQUESTED_RANGE_NOT_SATISFIABLE;
            } elseif (!$range->is_total) {
                $this->status = Status::PARTIAL_CONTENT;
            }
        }

        if ($this->request->headers->cache_control->cacheable != 'no-cache' && !$this->is_modified) {
            $this->status = Status::NOT_MODIFIED;
        }

        parent::__invoke();
    }

    /**
     * The following headers are always modified:
     *
     * - `Cache-Control`: sets _cacheable_ to _public_.
     * - `Expires`: is set to "+1 month".
     *
     * If the status code is `Stauts::NOT_MODIFIED` the following headers are unset:
     *
     * - `Content-Type`
     * - `Content-Length`
     *
     * Otherwise, the following header is set:
     *
     * - `Content-Type`:
     *
     * @inheritdoc
     */
    protected function finalize(Headers &$headers, &$body): void
    {
        parent::finalize($headers, $body);

        $status = $this->status->code;
        $expires = $this->expires;

        $headers->expires = $expires;
        $headers->cache_control->cacheable = 'public';
        $headers->cache_control->max_age = $expires->timestamp - DateTime::now()->timestamp;

        if ($status === Status::NOT_MODIFIED) {
            $this->finalize_for_not_modified($headers);

            return;
        }

        if ($status === Status::PARTIAL_CONTENT) {
            $this->finalize_for_partial_content($headers);

            return;
        }

        $this->finalize_for_other($headers);
    }

    /**
     * Finalizes the response for `Status::NOT_MODIFIED`.
     */
    private function finalize_for_not_modified(Headers &$headers): void
    {
        $headers->content_length = null;
    }

    /**
     * Finalizes the response for `Status::PARTIAL_CONTENT`.
     */
    private function finalize_for_partial_content(Headers &$headers): void
    {
        $range = $this->range;

        $headers->last_modified = $this->modified_time;
        $headers['Content-Range'] = (string) $range;
        $headers->content_length = $range->length;
    }

    /**
     * Finalizes the response for status other than `Status::NOT_MODIFIED` or
     * `Status::PARTIAL_CONTENT`.
     */
    private function finalize_for_other(Headers &$headers): void
    {
        $headers->last_modified = $this->modified_time;

        if (!$headers['Accept-Ranges']) {
            $request = $this->request;

            $headers['Accept-Ranges'] = $request->method->is_get() || $request->method->is_head() ? 'bytes' : 'none';
        }

        $headers->content_length = $this->file->getSize();
    }

    /**
     * Sends the file.
     *
     * @param SplFileInfo $file
     *
     * @codeCoverageIgnore
     */
    protected function send_file(SplFileInfo $file): void
    {
        [ $max_length, $offset ] = $this->resolve_max_length_and_offset();

        $out = fopen('php://output', 'wb');
        $source = fopen($file->getPathname(), 'rb');

        stream_copy_to_stream($source, $out, $max_length, $offset);

        fclose($out);
        fclose($source);
    }

    /**
     * Resolves `max_length` and `offset` parameters for stream copy.
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
     * Returns a SHA-384 of the file.
     */
    private function make_etag(): string
    {
        return self::hash_file($this->file->getPathname());
    }

    private function ensure_etag(): string
    {
        return $this->headers->etag ??= $this->make_etag();
    }

    /**
     * If the date returned by the parent is empty the method returns a date created from
     * {@link DEFAULT_EXPIRES}.
     */
    protected function get_expires(): Headers\Date
    {
        $expires = parent::get_expires();

        if (!$expires->is_empty) {
            return $expires;
        }

        return Headers\Date::from(self::DEFAULT_EXPIRES); // @phpstan-ignore-line
    }

    /**
     * Returns the timestamp at which the file was last modified.
     */
    protected function get_modified_time(): false|int
    {
        return $this->file->getMTime();
    }

    /**
     * Whether the file as been modified since the last response.
     *
     * The file is considered modified if one of the following conditions is met:
     *
     * - The `If-Modified-Since` request header is empty.
     * - The `If-Modified-Since` value is inferior to `$modified_time`.
     * - The `If-None-Match` value doesn't match `$etag`.
     */
    protected function get_is_modified(): bool
    {
        $headers = $this->request->headers;

        // HTTP/1.1

        if ((string) $headers[Headers::HEADER_IF_NONE_MATCH] !== $this->headers->etag) {
            return true;
        }

        // HTTP/1.0

        $if_modified_since = $headers->if_modified_since;

        return $if_modified_since->is_empty || $if_modified_since->timestamp < $this->modified_time;
    }

    private ?RequestRange $range_;

    protected function get_range(): ?RequestRange
    {
        return $this->range_ ??= RequestRange::from(
            $this->request->headers,
            $this->file->getSize(),
            $this->headers->etag
        );
    }
}
