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

use ArrayAccess;
use ArrayIterator;
use ICanBoogie\Accessor\AccessorTrait;
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
 * `Content-Type` and `Cache-Control`. For instance a {@link Headers\CacheControl} instance
 * is used to handle the directives of the `Cache-Control` header field.
 *
 * @see http://tools.ietf.org/html/rfc2616#section-14
 *
 * @property Headers\CacheControl|mixed $cache_control
 *     Shortcut to the `Cache-Control` header field definition.
 * @property Headers\ContentDisposition|mixed $content_disposition
 *     Shortcut to the `Content-Disposition` header field definition.
 * @property int|null $content_length
 *     Shortcut to the `Content-Length` header field definition.
 * @property Headers\ContentType|mixed $content_type
 *     Shortcut to the `Content-Type` header field definition.
 * @property Headers\Date|mixed $date
 *     Shortcut to the `Date` header field definition.
 * @property string|null $etag
 *     Shortcut to the `ETag` header field definition.
 * @property Headers\Date|mixed $expires
 *     Shortcut to the `Expires` header field definition.
 * @property Headers\Date|mixed $if_modified_since
 *     Shortcut to the `If-Modified-Since` header field definition.
 * @property Headers\Date|mixed $if_unmodified_since
 *     Shortcut to the `If-Unmodified-Since` header field definition.
 * @property Headers\Date|mixed $last_modified
 *     Shortcut to the `Last-Modified` header field definition.
 * @property string|null $location
 *     Shortcut to the `Location` header field definition.
 */
class Headers implements ArrayAccess, IteratorAggregate
{
    /**
     * @uses get_cache_control
     * @uses set_cache_control
     * @uses get_content_disposition
     * @uses set_content_disposition
     * @uses get_content_length
     * @uses set_content_length
     * @uses get_content_type
     * @uses set_content_type
     * @uses get_date
     * @uses set_date
     * @uses get_etag
     * @uses set_etag
     * @uses get_expires
     * @uses set_expires
     * @uses get_if_modified_since
     * @uses set_if_modified_since
     * @uses get_if_unmodified_since
     * @uses set_if_unmodified_since
     * @uses get_last_modified
     * @uses set_last_modified
     * @uses get_location
     * @uses set_location
     */
    use AccessorTrait;

    public const HEADER_CACHE_CONTROL = 'Cache-Control';
    public const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
    public const HEADER_CONTENT_LENGTH = 'Content-Length';
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_DATE = 'Date';
    public const HEADER_ETAG = 'ETag';
    public const HEADER_EXPIRES = 'Expires';
    public const HEADER_IF_MODIFIED_SINCE = 'If-Modified-Since';
    public const HEADER_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    public const HEADER_IF_NONE_MATCH = 'If-None-Match';
    public const HEADER_LAST_MODIFIED = 'Last-Modified';
    public const HEADER_LOCATION = 'Location';

    private const MAPPING = [

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
            $value = (string) $value;

            if ($value === '') {
                continue;
            }

            $header .= "$field: $value\r\n";
        }

        return $header;
    }

    /**
     * Sends header fields using the {@link header()} function.
     *
     * Header fields with empty string values are discarded.
     */
    public function __invoke(): void
    {
        foreach ($this->fields as $field => $value) {
            $value = (string) $value;

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
        return isset($this->fields[(string) $offset]);
    }

    /**
     * Returns a header.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (isset(self::MAPPING[$offset])) {
            if (empty($this->fields[$offset])) {
                /* @var $class Headers\Header|class-string */
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
     * > **Note:** Setting a header field to `null` removes it, just like unset() would.
     *
     * **Date, Expires, Last-Modified**
     *
     * The `Date`, `Expires` and `Last-Modified` header fields can be provided as a Unix
     * timestamp, a string or a {@link \DateTime} object.
     *
     * **Cache-Control, Content-Disposition and Content-Type**
     *
     * Instances of the {@link Headers\CacheControl}, {@link Headers\ContentDisposition} and
     * {@link Headers\ContentType} are used to handle the values of the `Cache-Control`,
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
            case 'Retry-After':
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

    private function get_cache_control(): Headers\CacheControl
    {
        return $this->offsetGet(self::HEADER_CACHE_CONTROL);
    }

    private function set_cache_control(mixed $value): void
    {
        $this->offsetSet(self::HEADER_CACHE_CONTROL, $value);
    }

    private function get_content_length(): ?int
    {
        return $this->offsetGet(self::HEADER_CONTENT_LENGTH);
    }

    private function set_content_length(?int $value): void
    {
        $this->offsetSet(self::HEADER_CONTENT_LENGTH, $value);
    }

    private function get_content_disposition(): Headers\ContentDisposition
    {
        return $this->offsetGet(self::HEADER_CONTENT_DISPOSITION);
    }

    private function set_content_disposition(mixed $value): void
    {
        $this->offsetSet(self::HEADER_CONTENT_DISPOSITION, $value);
    }

    private function get_content_type(): Headers\ContentType
    {
        return $this->offsetGet(self::HEADER_CONTENT_TYPE);
    }

    private function set_content_type(mixed $value): void
    {
        $this->offsetSet(self::HEADER_CONTENT_TYPE, $value);
    }

    private function get_date(): Headers\Date
    {
        return $this->offsetGet(self::HEADER_DATE);
    }

    private function set_date(mixed $value): void
    {
        $this->offsetSet(self::HEADER_DATE, $value);
    }

    private function get_etag(): ?string
    {
        return $this->offsetGet(self::HEADER_ETAG);
    }

    private function set_etag(?string $value): void
    {
        $this->offsetSet(self::HEADER_ETAG, $value);
    }

    private function get_expires(): Headers\Date
    {
        return $this->offsetGet(self::HEADER_EXPIRES);
    }

    private function set_expires(mixed $value): void
    {
        $this->offsetSet(self::HEADER_EXPIRES, $value);
    }

    private function get_if_modified_since(): Headers\Date
    {
        return $this->offsetGet(self::HEADER_IF_MODIFIED_SINCE);
    }

    private function set_if_modified_since(mixed $value): void
    {
        $this->offsetSet(self::HEADER_IF_MODIFIED_SINCE, $value);
    }

    private function get_if_unmodified_since(): Headers\Date
    {
        return $this->offsetGet(self::HEADER_IF_UNMODIFIED_SINCE);
    }

    private function set_if_unmodified_since(mixed $value): void
    {
        $this->offsetSet(self::HEADER_IF_UNMODIFIED_SINCE, $value);
    }

    private function get_last_modified(): Headers\Date
    {
        return $this->offsetGet(self::HEADER_LAST_MODIFIED);
    }

    private function set_last_modified(mixed $value): void
    {
        $this->offsetSet(self::HEADER_LAST_MODIFIED, $value);
    }

    private function get_location(): ?string
    {
        return $this->offsetGet(self::HEADER_LOCATION);
    }

    private function set_location(?string $value): void
    {
        $this->offsetSet(self::HEADER_LOCATION, $value);
    }
}
