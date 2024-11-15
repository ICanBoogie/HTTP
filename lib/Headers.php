<?php

namespace ICanBoogie\HTTP;

use ArrayAccess;
use ArrayIterator;
use DateTimeInterface;
use ICanBoogie\HTTP\Headers\Header;
use InvalidArgumentException;
use IteratorAggregate;

use function header;
use function is_numeric;
use function is_object;
use function is_string;
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
 * @link https://tools.ietf.org/html/rfc2616#section-14
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

    private static function normalize_field_name(string $name): string
    {
        return mb_convert_case(strtr(substr($name, 5), '_', '-'), MB_CASE_TITLE);
    }

    /**
     * @var array<string, Header|mixed>
     */
    private array $fields = [];

    /**
     * If the `REQUEST_URI` key is found in the header fields they are considered coming from the
     * super global `$_SERVER` array in which case they are filtered to keep only keys
     * starting with the `HTTP_` prefix. Also, header field names are normalized. For instance,
     * `HTTP_CONTENT_TYPE` becomes `Content-Type`.
     *
     * @param array<string, mixed> $fields The initial headers.
     */
    public function __construct(array $fields = [])
    {
        if (isset($fields['REQUEST_URI'])) {
            foreach ($fields as $field => $value) {
                if (!str_starts_with($field, 'HTTP_')) {
                    continue;
                }

                $field = self::normalize_field_name($field);

                $this[$field] = $value;
            }
        } else {
            foreach ($fields as $field => $value) {
                if (str_starts_with($field, 'HTTP_')) {
                    $field = self::normalize_field_name($field);
                }

                $this[$field] = $value;
            }
        }
    }

    public function __clone()
    {
        foreach ($this->fields as &$field) {
            if (!is_object($field)) {
                continue;
            }

            $field = clone $field;
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

        foreach ($this->fields as $field => $value) {
            $value = (string)$value;

            if ($value === '') {
                continue;
            }

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
        foreach ($this->fields as $field => $value) {
            $value = (string)$value;

            if ($value === '') {
                continue;
            }

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
     * Checks if a header field exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fields[(string)$offset]);
    }

    /**
     * Returns a header.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (isset(self::MAPPING[$offset])) {
            if (empty($this->fields[$offset])) {
                /* @var $class class-string<Headers\Header> */
                $class = self::MAPPING[$offset];
                $this->fields[$offset] = $class::from(null);
            }

            return $this->fields[$offset];
        }

        return $this->fields[$offset] ?? null;
    }

    /**
     * Sets a header field.
     *
     * > **Note**: Setting a header field to `null` removes it, just like unset() would.
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

        switch ($offset) {
            # http://tools.ietf.org/html/rfc2616#section-14.25
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

            # http://tools.ietf.org/html/rfc2616#section-14.37
            case self::HEADER_RETRY_AFTER:
                $value = is_numeric($value) ? $value : Headers\Date::from($value);
                break;
        }

        if (isset(self::MAPPING[$offset])) {
            /* @var $class Headers\Header|string */
            $class = self::MAPPING[$offset];
            $value = $class::from($value);
        }

        $this->fields[$offset] = $value;
    }

    /**
     * Removes a header field.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->fields[$offset]);
    }

    /**
     * Returns an iterator for the header fields.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->fields);
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
        get => $this->offsetGet(self::HEADER_CONTENT_LENGTH);
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
        set {
            $this->offsetSet(self::HEADER_CONTENT_DISPOSITION, $value);
        }
    }

    /**
     * Shortcut to the `Content-Type` header field definition.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
     */
    public Headers\ContentType|string|null $content_type {
        get => $this->offsetGet(self::HEADER_CONTENT_TYPE);
        set {
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
        get => $this->offsetGet(self::HEADER_ETAG);
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
        get => $this->offsetGet(self::HEADER_LOCATION);
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
        get => $this->offsetGet(self::HEADER_RETRY_AFTER);
        set(Headers\Date|DateTimeInterface|int|string|null $value) {
            $this->offsetSet(self::HEADER_RETRY_AFTER, $value);
        }
    }
}
