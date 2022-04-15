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
 *  @property Headers\CacheControl|mixed $cache_control
 *     Shortcut to the `Cache-Control` header field definition.
 *  @property Headers\ContentDisposition|mixed $content_disposition
 *     Shortcut to the `Content-Disposition` header field definition.
 *  @property Headers\ContentType|mixed $content_type
 *     Shortcut to the `Content-Type` header field definition.
 */
class Headers implements ArrayAccess, IteratorAggregate
{
    /**
     * @uses get_cache_control
     * @uses set_cache_control
     * @uses get_content_disposition
     * @uses set_content_disposition
     * @uses get_content_type
     * @uses set_content_type
     */
    use AccessorTrait;

    private const MAPPING = [

        'Cache-Control' => Headers\CacheControl::class,
        'Content-Disposition' => Headers\ContentDisposition::class,
        'Content-Type' => Headers\ContentType::class,
        'Date' => Headers\Date::class,
        'Expires' => Headers\Date::class,
        'If-Modified-Since' => Headers\Date::class,
        'If-Unmodified-Since' => Headers\Date::class,
        'Last-Modified' => Headers\Date::class,

    ];

    private static function normalize_field_name(string $name): string
    {
        return mb_convert_case(strtr(substr($name, 5), '_', '-'), MB_CASE_TITLE);
    }

    /**
     * Header fields.
     */
    private array $fields = [];

    /**
     * If the `REQUEST_URI` key is found in the header fields they are considered coming from the
     * super global `$_SERVER` array in which case they are filtered to keep only keys
     * starting with the `HTTP_` prefix. Also, header field names are normalized. For instance,
     * `HTTP_CONTENT_TYPE` becomes `Content-Type`.
     *
     * @param array $fields The initial headers.
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

    /**
     * Clone instantiated fields.
     */
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
     *
     * @return string
     */
    public function __toString()
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
     * Sends header fields using the {@link header()} function.
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
                /* @var $class Headers\Header|string */
                $class = self::MAPPING[$offset];
                $this->fields[$offset] = $class::from(null);
            }

            return $this->fields[$offset];
        }

        return $this->offsetExists($offset) ? $this->fields[$offset] : null;
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
            case 'If-Modified-Since':
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

    public function get_cache_control(): Headers\CacheControl
    {
        return $this->offsetGet('Cache-Control');
    }

    public function set_cache_control(mixed $value): void
    {
        $this->offsetSet('Cache-Control', $value);
    }

    public function get_content_disposition(): Headers\ContentDisposition
    {
        return $this->offsetGet('Content-Disposition');
    }

    public function set_content_disposition(mixed $value): void
    {
        $this->offsetSet('Content-Disposition', $value);
    }

    public function get_content_type(): Headers\ContentType
    {
        return $this->offsetGet('Content-Type');
    }

    public function set_content_type(mixed $value): void
    {
        $this->offsetSet('Content-Type', $value);
    }

//'Date' => Headers\Date::class,
//'Expires' => Headers\Date::class,
//'If-Modified-Since' => Headers\Date::class,
//'If-Unmodified-Since' => Headers\Date::class,
//'Last-Modified' => Headers\Date::class,

}
