<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use ArrayAccess;
use DateTimeInterface;
use Generator;
use ICanBoogie\HTTP\Headers\Header;
use InvalidArgumentException;
use IteratorAggregate;

use function header;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match;
use function str_starts_with;
use function strtolower;
use function mb_convert_case;
use function strpos;
use function strtr;
use function substr;

/**
 * HTTP Header field definitions.
 *
 * Instances of this class are used to collect and manipulate HTTP header field definitions.
 * Header field instances are used to handle the definition of complex header fields such as
 * `Content-Type` and `Cache-Control`. For instance a {@see Headers\CacheControl} instance
 * is used to handle the directives of the `Cache-Control` header field.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9110#section-5
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class Headers implements ArrayAccess, IteratorAggregate
{
    public const string HEADER_ACCEPT_RANGES = 'Accept-Ranges';
    public const string HEADER_CACHE_CONTROL = 'Cache-Control';
    public const string HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
    public const string HEADER_CONTENT_LENGTH = 'Content-Length';
    public const string HEADER_CONTENT_TYPE = 'Content-Type';
    public const string HEADER_DATE = 'Date';
    public const string HEADER_ETAG = 'ETag';
    public const string HEADER_EXPIRES = 'Expires';
    public const string HEADER_IF_MODIFIED_SINCE = 'If-Modified-Since';
    public const string HEADER_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    public const string HEADER_IF_NONE_MATCH = 'If-None-Match';
    public const string HEADER_IF_RANGE = 'If-Range';
    public const string HEADER_LAST_MODIFIED = 'Last-Modified';
    public const string HEADER_LOCATION = 'Location';
    public const string HEADER_RANGE = 'Range';
    public const string HEADER_RETRY_AFTER = 'Retry-After';

    private const array MAPPING = [

        self::HEADER_CACHE_CONTROL => Headers\CacheControl::class,
        self::HEADER_CONTENT_DISPOSITION => Headers\ContentDisposition::class,
        self::HEADER_CONTENT_TYPE => Headers\ContentType::class,
        self::HEADER_DATE => Headers\Date::class,
        self::HEADER_EXPIRES => Headers\Date::class,
        self::HEADER_IF_MODIFIED_SINCE => Headers\Date::class,
        self::HEADER_IF_UNMODIFIED_SINCE => Headers\Date::class,
        self::HEADER_LAST_MODIFIED => Headers\Date::class,

    ];

    /**
     * CGI exposes these request header fields without the `HTTP_` prefix.
     */
    private const array CGI_CONTENT_FIELDS = [

        'CONTENT_LENGTH' => self::HEADER_CONTENT_LENGTH,
        'CONTENT_TYPE' => self::HEADER_CONTENT_TYPE,

    ];

    private static function normalize_field_name(string $name): string
    {
        return mb_convert_case(strtr(substr($name, 5), '_', '-'), MB_CASE_TITLE);
    }

    /**
     * Header field names defined by the `HEADER_*` constants, indexed by their lowercase form.
     */
    private const array KNOWN_NAMES = [

        'accept-ranges' => self::HEADER_ACCEPT_RANGES,
        'cache-control' => self::HEADER_CACHE_CONTROL,
        'content-disposition' => self::HEADER_CONTENT_DISPOSITION,
        'content-length' => self::HEADER_CONTENT_LENGTH,
        'content-type' => self::HEADER_CONTENT_TYPE,
        'date' => self::HEADER_DATE,
        'etag' => self::HEADER_ETAG,
        'expires' => self::HEADER_EXPIRES,
        'if-modified-since' => self::HEADER_IF_MODIFIED_SINCE,
        'if-unmodified-since' => self::HEADER_IF_UNMODIFIED_SINCE,
        'if-none-match' => self::HEADER_IF_NONE_MATCH,
        'if-range' => self::HEADER_IF_RANGE,
        'last-modified' => self::HEADER_LAST_MODIFIED,
        'location' => self::HEADER_LOCATION,
        'range' => self::HEADER_RANGE,
        'retry-after' => self::HEADER_RETRY_AFTER,

    ];

    /**
     * Returns the canonical spelling of a header field name.
     *
     * Names defined by the `HEADER_*` constants use the spelling of the constant, others are
     * returned as is.
     *
     * @throws InvalidArgumentException if the name is not a valid HTTP field name.
     */
    private static function canonical_name(string $name): string
    {
        if (!preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $name)) {
            throw new InvalidArgumentException("Invalid header field name: '$name'.");
        }

        return self::KNOWN_NAMES[strtolower($name)] ?? $name;
    }

    /**
     * Rejects values that would allow header injection.
     *
     * @throws InvalidArgumentException
     */
    private static function assert_value_is_safe(string $field, string $value): void
    {
        if (preg_match('/[\x00\r\n]/', $value)) {
            throw new InvalidArgumentException("Invalid value for header field '$field': control characters are not allowed.");
        }
    }

    /**
     * Header fields indexed by their lowercase name, the field names being case-insensitive.
     * Each entry holds the canonical name of the field, and its value.
     *
     * @var array<string, array{ string, Header|mixed }>
     */
    private array $fields = [];

    /**
     * If the `REQUEST_URI` key is found in the header fields they are considered coming from the
     * super global `$_SERVER` array in which case they are filtered to keep only keys
     * starting with the `HTTP_` prefix, plus `CONTENT_TYPE` and `CONTENT_LENGTH`, which CGI exposes
     * without the prefix. Also, header field names are normalized. For instance,
     * `HTTP_USER_AGENT` becomes `User-Agent` and `CONTENT_TYPE` becomes `Content-Type`.
     *
     * @param array<string, mixed> $fields The initial headers.
     */
    public function __construct(array $fields = [])
    {
        $from_server = isset($fields['REQUEST_URI']);

        foreach ($fields as $field => $value) {
            if (str_starts_with($field, 'HTTP_')) {
                $field = self::normalize_field_name($field);
            } elseif (isset(self::CGI_CONTENT_FIELDS[$field])) {
                // CGI defines these keys even when the request has no body.
                if ($value === '') {
                    continue;
                }

                $field = self::CGI_CONTENT_FIELDS[$field];
            } elseif ($from_server) {
                continue;
            }

            $this[$field] = $value;
        }
    }

    public function __clone()
    {
        foreach ($this->fields as &$entry) {
            if (!is_object($entry[1])) {
                continue;
            }

            $entry[1] = clone $entry[1];
        }
    }

    /**
     * Returns the header as a string.
     *
     * Header fields with empty string values are discarded.
     */
    public function __toString(): string
    {
        $header = '';

        foreach ($this->fields as [ $field, $value ]) {
            $value = (string)$value;

            if ($value === '') {
                continue;
            }

            self::assert_value_is_safe($field, $value);

            $header .= "$field: $value\r\n";
        }

        return $header;
    }

    /**
     * Sends header fields using the {@see header()} function.
     *
     * Header fields with empty string values are discarded.
     */
    public function __invoke(): void
    {
        foreach ($this->fields as [ $field, $value ]) {
            $value = (string)$value;

            if ($value === '') {
                continue;
            }

            self::assert_value_is_safe($field, $value);

            $this->send_header($field, $value);
        }
    }

    /**
     * Send header field.
     *
     * Note: The only reason for this method is testing.
     *
     * @param string $field
     * @param string $value
     */
    protected function send_header(string $field, string $value): void // @codeCoverageIgnoreStart
    {
        header("$field: $value");
    }// @codeCoverageIgnoreEnd

    /**
     * Checks if a header field exists. Field names are case-insensitive.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fields[strtolower((string)$offset)]);
    }

    /**
     * Returns a header.
     *
     * **Note**: Header fields handled by a {@see Header} class (`Cache-Control`, `Content-Type`,
     * `Date`...) are created empty on first read, and stored, so that
     * `$headers->cache_control->max_age = 60` modifies the headers. Empty fields are not
     * rendered, but {@see offsetExists()} returns `true` once they have been read.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $key = strtolower((string)$offset);
        $name = self::KNOWN_NAMES[$key] ?? (string)$offset;

        if (isset(self::MAPPING[$name])) {
            if (empty($this->fields[$key][1])) {
                /* @var $class class-string<Headers\Header> */
                $class = self::MAPPING[$name];
                $this->fields[$key] = [ $name, $class::from(null) ];
            }

            return $this->fields[$key][1];
        }

        return $this->fields[$key][1] ?? null;
    }

    /**
     * Sets a header field.
     *
     * > **Note**: Setting a header field to `null` removes it, just like unset() would.
     *
     * Field names are case-insensitive. Names defined by the `HEADER_*` constants are stored with
     * the spelling of the constant, other names keep the spelling they were first given.
     *
     * @throws InvalidArgumentException if the field name is not a valid HTTP token, or if the
     * value contains NUL, CR or LF characters.
     *
     * **Date, Expires, Last-Modified**
     *
     * The `Date`, `Expires` and `Last-Modified` header fields can be provided as a Unix
     * timestamp, a string or a {@see DateTimeInterface} object.
     *
     * **Cache-Control, Content-Disposition, Content-Type**
     *
     * Instances of the {@see Headers\CacheControl}, {@see Headers\ContentDisposition} and
     * {@see Headers\ContentType} are used to handle the values of the `Cache-Control`,
     * `Content-Disposition` and `Content-Type` header fields.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value === null) {
            unset($this[$offset]);

            return;
        }

        $offset = self::canonical_name((string)$offset);

        switch ($offset) {
            # https://www.rfc-editor.org/rfc/rfc9110#section-13.1.3
            case self::HEADER_IF_MODIFIED_SINCE:
                #
                # Removes the ";length=xxx" string added by Internet Explorer.
                # http://stackoverflow.com/questions/12626699/if-modified-since-http-header-passed-by-ie9-includes-length
                #

                if (is_string($value)) {
                    $pos = strpos($value, ';');

                    if ($pos) {
                        $value = substr($value, 0, $pos);
                    }
                }
                break;

            case self::HEADER_LOCATION:
                if ($value === '') {
                    throw new InvalidArgumentException('Cannot redirect to a blank URL.');
                }
                break;

            # https://www.rfc-editor.org/rfc/rfc9110#section-10.2.3
            case self::HEADER_RETRY_AFTER:
                $value = is_numeric($value) ? (int) $value : Headers\Date::from($value);
                break;
        }

        if (isset(self::MAPPING[$offset])) {
            /* @var $class Headers\Header|string */
            $class = self::MAPPING[$offset];
            $value = $class::from($value);
        }

        if (is_string($value)) {
            self::assert_value_is_safe($offset, $value);
        }

        $this->fields[strtolower($offset)] = [ $offset, $value ];
    }

    /**
     * Removes a header field.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->fields[strtolower((string)$offset)]);
    }

    /**
     * Returns an iterator for the header fields.
     */
    public function getIterator(): Generator
    {
        foreach ($this->fields as [ $field, $value ]) {
            yield $field => $value;
        }
    }

    /**
     * Shortcut to the `Cache-Control` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
     */
    public Headers\CacheControl $cache_control {
        get => $this->offsetGet(self::HEADER_CACHE_CONTROL);
        set (Headers\CacheControl|string $value) {
            $this->offsetSet(self::HEADER_CACHE_CONTROL, $value);
        }
    }

    /**
     * Shortcut to the `Content-Length` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Length
     */
    public ?int $content_length {
        get => ($value = $this->offsetGet(self::HEADER_CONTENT_LENGTH)) === null ? null : (int) $value;
        set {
            $this->offsetSet(self::HEADER_CONTENT_LENGTH, $value);
        }
    }

    /**
     * Shortcut to the `Content-Disposition` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition
     */
    public Headers\ContentDisposition $content_disposition {
        get => $this->offsetGet(self::HEADER_CONTENT_DISPOSITION);
        set(Headers\ContentDisposition|string|null $value) {
            $this->offsetSet(self::HEADER_CONTENT_DISPOSITION, $value);
        }
    }

    /**
     * Shortcut to the `Content-Type` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
     */
    public Headers\ContentType $content_type {
        get => $this->offsetGet(self::HEADER_CONTENT_TYPE);
        set(Headers\ContentType|string|null $value) {
            $this->offsetSet(self::HEADER_CONTENT_TYPE, $value);
        }
    }

    /**
     * Shortcut to the `Date` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Date
     */
    public Headers\Date|null $date {
        get => $this->offsetGet(self::HEADER_DATE);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_DATE, $value);
        }
    }

    /**
     * Shortcut to the `ETag` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/ETag
     */
    public ?string $etag {
        get => ($value = $this->offsetGet(self::HEADER_ETAG)) === null ? null : (string) $value;
        set {
            $this->offsetSet(self::HEADER_ETAG, $value);
        }
    }

    /**
     * Shortcut to the `Expires` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Expires
     */
    public Headers\Date|null $expires {
        get => $this->offsetGet(self::HEADER_EXPIRES);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_EXPIRES, $value);
        }
    }

    /**
     * Shortcut to the `If-Modified-Since` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Modified-Since
     */
    public Headers\Date|null $if_modified_since {
        get => $this->offsetGet(self::HEADER_IF_MODIFIED_SINCE);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_IF_MODIFIED_SINCE, $value);
        }
    }

    /**
     * Shortcut to the `If-Unmodified-Since` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Unmodified-Since
     */
    public Headers\Date|null $if_unmodified_since {
        get => $this->offsetGet(self::HEADER_IF_UNMODIFIED_SINCE);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_IF_UNMODIFIED_SINCE, $value);
        }
    }

    /**
     * Shortcut to the `Last-Modified` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Last-Modified
     */
    public Headers\Date|null $last_modified {
        get => $this->offsetGet(self::HEADER_LAST_MODIFIED);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_LAST_MODIFIED, $value);
        }
    }

    /**
     * Shortcut to the `Location` header field definition.
     * *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
     */
    public ?string $location {
        get => ($value = $this->offsetGet(self::HEADER_LOCATION)) === null ? null : (string) $value;
        set {
            $this->offsetSet(self::HEADER_LOCATION, $value);
        }
    }

    /**
     * Shortcut to the `Retry-After` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Retry-After
     */
    public Headers\Date|int|null $retry_after {
        get {
            $value = $this->offsetGet(self::HEADER_RETRY_AFTER);

            return is_numeric($value) ? (int) $value : $value;
        }
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_RETRY_AFTER, $value);
        }
    }
}
