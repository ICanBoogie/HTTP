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

use Closure;
use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;

use function ICanBoogie\format;
use function is_array;
use function is_object;
use function method_exists;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * A response to an HTTP request.
 *
 * @property Status|int $status
 * @property mixed $body The body of the response.
 *
 * @property int|null $ttl
 *     Adjusts the `s-maxage` directive of the `Cache-Control` header field definition according to
 *     the `Age` header field definition.
 * @property int|null $age Shortcut to the `Age` header field definition.
 * @property int $content_length Shortcut to the `Content-Length` header field definition.
 * @property Headers\Date|string $date @deprecated Shortcut to the `Date` header field definition.
 * @property string|null $etag Shortcut to the `ETag` header field definition.
 * @property Headers\Date $expires
 *     Adjusts the `Expires` header and the `max_age` directive of the `Cache-Control` header.
 * @property string|null $location Shortcut to the `Location` header field definition.
 *
 * @property-read bool $is_cacheable {@link get_is_cacheable()}
 * @property-read bool $is_fresh {@link get_is_fresh()}
 * @property-read bool $is_validateable {@link get_is_validateable()}
 *
 * @see http://tools.ietf.org/html/rfc2616
 */
class Response implements ResponseStatus
{
    /**
     * @uses get_cache_control
     * @uses set_cache_control
     * @uses get_content_type
     * @uses set_content_type
     * @uses get_expires
     * @uses set_expires
     * @uses get_last_modified
     * @uses set_last_modified
     */
    use AccessorTrait;

    public Headers $headers;

    /**
     * The HTTP protocol version (1.0 or 1.1), defaults to '1.1'
     */
    public string $version = '1.1';

    /**
     * Initializes the `$body`,  `$status`, `$headers`, and `$date` properties.
     *
     * @param mixed|null $body The body of the response.
     * @param int|Status $status The status code of the response.
     * @param array|Headers $headers The initial header fields of the response.
     */
    public function __construct(
        mixed $body = null,
        int|Status $status = self::STATUS_OK,
        Headers|array $headers = []
    ) {
        if (is_array($headers)) {
            $headers = new Headers($headers);
        } elseif (!$headers instanceof Headers) {
            throw new InvalidArgumentException(
                "\$headers must be an array or a ICanBoogie\\HTTP\\Headers instance. Given: " . gettype(
                    $headers
                )
            );
        }

        $this->headers = $headers;

        if ($this->headers->date->is_empty) {
            $this->headers->date = 'now';
        }

        $this->set_status($status);

        if ($body !== null) {
            $this->set_body($body);
        }
    }

    /**
     * Clones the `$headers` and `$status` properties.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->status = clone $this->status;
    }

    /**
     * Renders the response as an HTTP string.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $header = clone $this->headers;
            $body = $this->body;

            $this->finalize($header, $body);

            \ob_start();

            $this->send_body($body);

            $body = \ob_get_clean();

            return "HTTP/{$this->version} {$this->status}\r\n"
                . $header
                . "\r\n"
                . $body;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * Issues the HTTP response.
     *
     * {@link finalize()} is invoked to finalize the headers (a cloned actually)
     * and the body. {@link send_headers} is invoked to send the headers and {@link send_body()}
     *is invoked to send the body, if the body is not `null`.
     *
     * The body is not send in the following instances:
     *
     * - The finalized body is `null`.
     * - The status is not ok.
     */
    public function __invoke(): void
    {
        $headers = clone $this->headers;
        $body = $this->body;

        $this->finalize($headers, $body);
        $this->send_headers($headers);

        if ($body === null) {
            return;
        }

        $this->send_body($body);
    }

    /**
     * Finalize the body.
     *
     * Subclasses might want to override this method if they wish to alter the header or the body
     * before the response is sent or transformed into a string.
     *
     * @param Headers $headers Reference to the final header.
     * @param mixed $body Reference to the final body.
     */
    protected function finalize(Headers &$headers, &$body): void
    {
        if (
            $body instanceof Closure
            || !is_object($body)
            || !method_exists($body, '__toString')
        ) {
            return;
        }

        $body = (string)$body;
    }

    /**
     * Sends response headers.
     *
     * @param Headers $headers
     *
     * @return bool `true` is the headers were sent, `false` otherwise.
     */
    protected function send_headers(Headers $headers): bool // @codeCoverageIgnoreStart
    {
        if (\headers_sent($file, $line)) {
            \trigger_error(
                format(
                    "Cannot modify header information because"
                    . " it was already sent. Output started at !at.",
                    [

                        'at' => $file . ':' . $line,

                    ]
                )
            );

            return false;
        }

        \header_remove('Pragma');
        \header_remove('X-Powered-By');

        \header("HTTP/{$this->version} {$this->status}");

        $headers();

        return true;
    } // @codeCoverageIgnoreEnd

    /**
     * Sends response body.
     *
     * @param mixed $body
     */
    protected function send_body($body): void
    {
        if ($body instanceof Closure) {
            $body($this);

            return;
        }

        echo $body;
    }

    /**
     * Status of the HTTP response.
     */
    private Status $status;

    protected function set_status(int|Status $status): void
    {
        $this->status = Status::from($status);
    }

    protected function get_status(): Status
    {
        return $this->status;
    }

    /**
     * The response body.
     *
     * The body can be any data type that can be converted into a string. This includes numeric
     * and objects implementing the {@link __toString()} method.
     *
     * @var mixed
     */
    private $body;

    protected function set_body($body): void
    {
        $this->assert_body_is_valid($body);
        $this->body = $body;
    }

    /**
     * Assert that a body is valid.
     *
     * @param mixed $body
     *
     * @throws \UnexpectedValueException if the specified body doesn't meet the requirements.
     */
    protected function assert_body_is_valid($body)
    {
        if (
            $body === null
            || $body instanceof Closure
            || \is_numeric($body)
            || \is_string($body)
            || (\is_object($body) && method_exists($body, '__toString'))
        ) {
            return;
        }

        throw new \UnexpectedValueException(
            format(
                "The Response body must be a string,"
                . " an object implementing the __toString() method or be callable, %type given."
                . " !value",
                [

                    'type' => \gettype($body),
                    'value' => $body,

                ]
            )
        );
    }

    protected function get_body()
    {
        return $this->body;
    }

    /**
     * Sets the value of the `Location` header field.
     *
     * @param string|null $url
     */
    protected function set_location(?string $url)
    {
        if ($url !== null && !$url) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->headers['Location'] = $url;
    }

    /**
     * Returns the value of the `Location` header field.
     *
     * @return string|null
     */
    protected function get_location(): ?string
    {
        return $this->headers['Location'];
    }

    /**
     * Sets the value of the `Content-Length` header field.
     *
     * @param int|null $length
     */
    protected function set_content_length(?int $length): void
    {
        $this->headers['Content-Length'] = $length;
    }

    /**
     * Returns the value of the `Content-Length` header field.
     *
     * @return int|null
     */
    protected function get_content_length(): ?int
    {
        return $this->headers['Content-Length']; // @phpstan-ignore-line
    }

    /**
     * Sets the value of the `Date` header field.
     */
    protected function set_date(mixed $time): void
    {
        trigger_error('$response->date is deprecated use $response->headers->date instead.', E_USER_DEPRECATED);

        $this->headers->date = $time;
    }

    /**
     * Returns the value of the `Date` header field.
     */
    protected function get_date(): Headers\Date
    {
        trigger_error('$response->date is deprecated use $response->headers->date instead.', E_USER_DEPRECATED);

        return $this->headers->date;
    }

    /**
     * Sets the value of the `Age` header field.
     *
     * @param int|null $age
     */
    protected function set_age(?int $age): void
    {
        $this->headers['Age'] = $age;
    }

    /**
     * Returns the age of the response.
     *
     * @return int|null
     */
    protected function get_age(): ?int
    {
        $age = $this->headers['Age'];

        if ($age) {
            return (int) $age;
        }

        if (!$this->headers->date->is_empty) {
            return max(0, time() - $this->headers->date->utc->timestamp);
        }

        return null;
    }

    /**
     * Sets the value of the `Expires` header field.
     *
     * The method updates the `max-age` Cache Control directive accordingly.
     */
    protected function set_expires(mixed $time): void
    {
        $this->headers->expires = $time;
        $expires = $this->headers->expires;

        $this->headers->cache_control->max_age = $expires->is_empty ? null : $expires->timestamp - time();
    }

    /**
     * Returns the value of the `Expires` header field.
     */
    protected function get_expires(): Headers\Date
    {
        return $this->headers->expires;
    }

    /**
     * Sets the value of the `ETag` header field.
     *
     * @param string|null $value
     */
    protected function set_etag(?string $value): void
    {
        $this->headers['ETag'] = $value;
    }

    /**
     * Returns the value of the `ETag` header field.
     *
     * @return string|null
     */
    protected function get_etag(): ?string
    {
        return $this->headers['ETag'];
    }

    /**
     * @deprecated 6.0
     * @see Headers::$cache_control
     */
    protected function get_cache_control(): Headers\CacheControl
    {
        trigger_error('$response->cache_control is deprecated, use $response->headers->cache_control instead.', E_USER_DEPRECATED);

        return $this->headers->cache_control;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$cache_control
     */
    protected function set_cache_control(?string $cache_directives): void
    {
        trigger_error('$response->cache_control is deprecated, use $response->headers->cache_control instead.', E_USER_DEPRECATED);

        $this->headers->cache_control = $cache_directives;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_type
     */
    protected function get_content_type(): Headers\ContentType
    {
        trigger_error('$response->content_type is deprecated use $response->headers->content_type instead.', E_USER_DEPRECATED);

        return $this->headers->content_type;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_type
     */
    protected function set_content_type(mixed $content_type): void
    {
        trigger_error('$response->content_type is deprecated use $response->headers->content_type instead.', E_USER_DEPRECATED);

        $this->headers->content_type = $content_type;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$last_modified
     */
    private function get_last_modified(): Headers\Date
    {
        trigger_error('$response->last_modified is deprecated, use $response->headers->last_modified instead.', E_USER_DEPRECATED);

        return $this->headers->last_modified;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$last_modified
     */
    private function set_last_modified(mixed $value): void
    {
        trigger_error('$response->last_modified is deprecated, use $response->headers->last_modified instead.', E_USER_DEPRECATED);

        $this->headers->last_modified = $value;
    }

    /**
     * Sets the response's time-to-live for shared caches.
     *
     * This method adjusts the Cache-Control/s-maxage directive.
     *
     * @param int|null $seconds The number of seconds.
     */
    protected function set_ttl(?int $seconds): void
    {
        $this->headers->cache_control->s_maxage = $this->age + $seconds;
    }

    /**
     * Returns the response's time-to-live in seconds.
     *
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * re-validating with the origin.
     *
     * @return int|null The number of seconds to live, or `null` is no freshness information
     * is present.
     */
    protected function get_ttl(): ?int
    {
        $max_age = $this->headers->cache_control->max_age;

        if ($max_age) {
            return $max_age - $this->age;
        }

        return null;
    }

    /**
     * Whether the response includes header fields that can be used to validate the response
     * with the origin server using a conditional GET request.
     *
     * @return bool
     */
    protected function get_is_validateable(): bool
    {
        return !$this->headers['Last-Modified']->is_empty || $this->headers['ETag']; // @phpstan-ignore-line
    }

    /**
     * Whether the response is worth caching under any circumstance.
     *
     * Responses marked _private_ with an explicit `Cache-Control` directive are considered
     * not cacheable.
     *
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache validator
     * (`Last-Modified`, `ETag`) are considered not cacheable.
     *
     * @return bool
     */
    protected function get_is_cacheable(): bool
    {
        if (
            !$this->status->is_cacheable
            || $this->headers->cache_control->no_store
            || $this->headers->cache_control->cacheable == 'private'
        ) {
            return false;
        }

        return $this->is_validateable || $this->is_fresh;
    }

    /**
     * Whether the response is fresh.
     */
    protected function get_is_fresh(): bool
    {
        return $this->ttl > 0;
    }
}
