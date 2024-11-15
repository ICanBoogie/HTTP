<?php

namespace ICanBoogie\HTTP;

use Closure;
use Stringable;
use Throwable;

use function header;
use function header_remove;
use function headers_sent;
use function ob_get_clean;
use function ob_start;
use function trigger_error;

/**
 * A response to an HTTP request.
 *
 * @link https://tools.ietf.org/html/rfc2616
 */
class Response implements ResponseStatus
{
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
        Headers|array $headers = [],
    ) {
        if (!$headers instanceof Headers) {
            $headers = new Headers($headers);
        }

        $this->headers = $headers;

        if ($this->headers->date->is_empty) {
            $this->headers->date = 'now';
        }

        $this->status = $status;
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
     * {@see finalize()} is invoked to finalize the headers (a clone) and the body.
     * {@see send_headers} is invoked to send the headers,
     * and {@see send_body()} is invoked to send the body, if the body is not `null`.
     *
     * The body is not sent in the following instances:
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

        $body = (string)$body;
    }

    protected function send_headers(Headers $headers): bool // @codeCoverageIgnoreStart
    {
        if (headers_sent($file, $line)) {
            trigger_error(
                "Cannot modify header information because it was already sent. Output started at $file:$line",
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
    public Status $status {
        get => $this->status;
        set(Status|int $value) {
            $this->status = $value instanceof Status ? $value : new Status($value);
        }
    }

    public ?int $age {
        get {
            $age = $this->headers['Age'];

            if ($age) {
                return (int)$age;
            }

            if (!$this->headers->date->is_empty) {
                return max(0, time() - $this->headers->date->timestamp);
            }

            return null;
        }

        set => $this->headers['Age'] = $value;
    }

    public Headers\Date|null $expires {
        get => $this->headers->expires;
        set(Headers\Date|\DateTimeInterface|string|null $value) {
            $this->headers->expires = $value;
            $expires = $this->headers->expires;

            $this->headers->cache_control->max_age = $expires->is_empty ? null : $expires->timestamp - time();
        }
    }

    /**
     * The response time-to-live in second for shared caches.
     * Setting this property also adjusts the `s-maxage` directive of the `Cache-Control` header according to
     * the `Age` header.
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * re-validating with the origin.
     */
    public ?int $ttl {
        get {
            $max_age = $this->headers->cache_control->max_age;

            if ($max_age) {
                return $max_age - $this->age;
            }

            return null;
        }

        set => $this->headers->cache_control->s_maxage = $this->age + $value;
    }

    /**
     * Whether the response includes header fields that can be used to validate the response
     * with the origin server using a conditional GET request.
     */
    public bool $is_validateable
        {
            get => !$this->headers->last_modified->is_empty || $this->headers->etag;
        }

    /**
     * Whether the response is worth caching under any circumstance.
     * Responses marked _private_ with an explicit `Cache-Control` directive are considered
     * not cacheable. Responses with neither a freshness lifetime (Expires, max-age) nor cache validator
     * (`Last-Modified`, `ETag`) are considered not cacheable.
     */
    public bool $is_cacheable
        {
            get {
                if (
                    !$this->status->is_cacheable
                    || $this->headers->cache_control->no_store
                    || $this->headers->cache_control->cacheable == 'private'
                ) {
                    return false;
                }

                return $this->is_validateable || $this->is_fresh;
            }
        }

    /**
     * Whether the response is fresh.
     * A response is considered fresh when its TTL is greater than 0.
     */
    public bool $is_fresh
        {
            get => $this->ttl > 0;
        }
}
