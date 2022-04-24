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
use Stringable;
use Throwable;

use function header;
use function header_remove;
use function headers_sent;
use function ICanBoogie\format;
use function ob_get_clean;
use function ob_start;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * A response to an HTTP request.
 *
 * @property Status|int $status
 *     The status of the response.
 * @property int|null $ttl
 *     The response's time-to-live in second for shared caches.
 *     Setting this property also adjust the `s-maxage` directive of the `Cache-Control` header according to
 *     the `Age` header.
 *     When the responses TTL is <= 0, the response may not be served from cache without first
 *     re-validating with the origin.
 * @property int|null $age
 *     Shortcut to the `Age` header.
 * @property Headers\Date|mixed $expires
 *     Adjusts the `Expires` header and the `max_age` directive of the `Cache-Control` header.
 * @property-read bool $is_cacheable
 *     Whether the response is worth caching under any circumstance.
 *     Responses marked _private_ with an explicit `Cache-Control` directive are considered
 *     not cacheable. Responses with neither a freshness lifetime (Expires, max-age) nor cache validator
 *     (`Last-Modified`, `ETag`) are considered not cacheable.
 * @property-read bool $is_fresh
 *     Whether the response is fresh.
 *     A response is considered fresh when its TTL is greater than 0.
 * @property-read bool $is_validateable
 *     Whether the response includes header fields that can be used to validate the response
 *     with the origin server using a conditional GET request.
 *
 * @see http://tools.ietf.org/html/rfc2616
 */
class Response implements ResponseStatus
{
    /**
     * @uses get_age
     * @uses set_age
     * @uses get_cache_control
     * @uses set_cache_control
     * @uses get_content_length
     * @uses set_content_length
     * @uses get_content_type
     * @uses set_content_type
     * @uses get_etag
     * @uses set_etag
     * @uses get_expires
     * @uses set_expires
     * @uses get_date
     * @uses set_date
     * @uses get_is_cacheable
     * @uses get_is_fresh
     * @uses get_is_validateable
     * @uses get_last_modified
     * @uses set_last_modified
     * @uses get_location
     * @uses set_location
     * @uses get_status
     * @uses set_status
     * @uses get_ttl
     * @uses set_ttl
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
     * @param int|Status $status The status code of the response.
     * @param array<string, mixed>|Headers $headers The initial header fields of the response.
     */
    public function __construct(
        public string|Stringable|Closure|null $body = null,
        int|Status $status = ResponseStatus::STATUS_OK,
        Headers|array $headers = []
    ) {
        if (!$headers instanceof Headers) {
            $headers = new Headers($headers);
        }

        $this->headers = $headers;

        if ($this->headers->date->is_empty) {
            $this->headers->date = 'now';
        }

        $this->set_status($status);
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
     */
    public function __toString(): string
    {
        try {
            $header = clone $this->headers;
            $body = $this->body;

            $this->finalize($header, $body);

            ob_start();

            $this->send_body($body);

            $body = ob_get_clean();

            return "HTTP/$this->version $this->status\r\n"
                . $header
                . "\r\n"
                . $body;
        } catch (Throwable $e) {
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
    protected function finalize(Headers &$headers, mixed &$body): void
    {
        if ($body instanceof Closure || !$body instanceof Stringable) {
            return;
        }

        $body = (string) $body;
    }

    protected function send_headers(Headers $headers): bool // @codeCoverageIgnoreStart
    {
        if (headers_sent($file, $line)) {
            trigger_error(
                format("Cannot modify header information because it was already sent. Output started at !at.", [
                    'at' => $file . ':' . $line,
                ])
            );

            return false;
        }

        header_remove('Pragma');
        header_remove('X-Powered-By');

        header("HTTP/$this->version $this->status");

        $headers();

        return true;
    } // @codeCoverageIgnoreEnd

    protected function send_body(mixed $body): void
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

    private function set_status(int|Status $status): void
    {
        $this->status = Status::from($status);
    }

    private function get_status(): Status
    {
        return $this->status;
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
     * @deprecated 6.0
     * @see Headers::$cache_control
     */
    protected function get_cache_control(): Headers\CacheControl
    {
        trigger_error(
            '$response->cache_control is deprecated, use $response->headers->cache_control instead.',
            E_USER_DEPRECATED
        );

        return $this->headers->cache_control;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$cache_control
     */
    protected function set_cache_control(?string $cache_directives): void
    {
        trigger_error(
            '$response->cache_control is deprecated, use $response->headers->cache_control instead.',
            E_USER_DEPRECATED
        );

        $this->headers->cache_control = $cache_directives;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_length
     */
    private function get_content_length(): ?int
    {
        trigger_error(
            '$response->content_length is deprecated use $response->headers->content_length instead.',
            E_USER_DEPRECATED
        );

        return $this->headers->content_length;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_length
     */
    private function set_content_length(?int $length): void
    {
        trigger_error(
            '$response->content_length is deprecated use $response->headers->content_length instead.',
            E_USER_DEPRECATED
        );

        $this->headers->content_length = $length;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_type
     */
    protected function get_content_type(): Headers\ContentType
    {
        trigger_error(
            '$response->content_type is deprecated use $response->headers->content_type instead.',
            E_USER_DEPRECATED
        );

        return $this->headers->content_type;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$content_type
     */
    protected function set_content_type(mixed $content_type): void
    {
        trigger_error(
            '$response->content_type is deprecated use $response->headers->content_type instead.',
            E_USER_DEPRECATED
        );

        $this->headers->content_type = $content_type;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$etag
     */
    private function get_etag(): ?string
    {
        trigger_error('$response->etag is deprecated use $response->headers->etag instead.', E_USER_DEPRECATED);

        return $this->headers->etag;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$etag
     */
    private function set_etag(?string $value): void
    {
        trigger_error('$response->etag is deprecated use $response->headers->etag instead.', E_USER_DEPRECATED);

        $this->headers->etag = $value;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$last_modified
     */
    private function get_last_modified(): Headers\Date
    {
        trigger_error(
            '$response->last_modified is deprecated, use $response->headers->last_modified instead.',
            E_USER_DEPRECATED
        );

        return $this->headers->last_modified;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$last_modified
     */
    private function set_last_modified(mixed $value): void
    {
        trigger_error(
            '$response->last_modified is deprecated, use $response->headers->last_modified instead.',
            E_USER_DEPRECATED
        );

        $this->headers->last_modified = $value;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$location
     */
    private function get_location(): ?string
    {
        trigger_error(
            '$response->location is deprecated, use $response->headers->location instead.',
            E_USER_DEPRECATED
        );

        return $this->headers->location;
    }

    /**
     * @deprecated 6.0
     * @see Headers::$location
     */
    private function set_location(?string $url): void
    {
        trigger_error(
            '$response->location is deprecated, use $response->headers->location instead.',
            E_USER_DEPRECATED
        );

        $this->headers->location = $url;
    }

    private function get_ttl(): ?int
    {
        $max_age = $this->headers->cache_control->max_age;

        if ($max_age) {
            return $max_age - $this->age;
        }

        return null;
    }

    private function set_ttl(?int $seconds): void
    {
        $this->headers->cache_control->s_maxage = $this->age + $seconds;
    }

    private function get_is_validateable(): bool
    {
        return !$this->headers->last_modified->is_empty || $this->headers->etag;
    }

    private function get_is_cacheable(): bool
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

    private function get_is_fresh(): bool
    {
        return $this->ttl > 0;
    }
}
