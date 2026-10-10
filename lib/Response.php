<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Closure;
use Stringable;
use Throwable;

use function is_numeric;
use function max;
use function time;

/**
 * A response to an HTTP request.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9110
 */
class Response implements ResponseStatus
{
    public Headers $headers;

    /**
     * The HTTP protocol version (1.0 or 1.1), defaults to '1.1'
     *
     * @throws \InvalidArgumentException if the version is not '1.0' or '1.1'.
     */
    public string $version = '1.1' {
        set {
            if ($value !== '1.0' && $value !== '1.1') {
                throw new \InvalidArgumentException("Unsupported HTTP version: '$value'. Expected '1.0' or '1.1'.");
            }

            $this->version = $value;
        }
    }

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
     * Resolves the status, headers and body as they are sent to the client, without modifying the
     * response.
     *
     * The headers are cloned, a {@see Stringable} body is converted to a string, and the body is
     * discarded for the statuses that cannot have one (`1xx`, `204`, and `304`), see RFC 9110
     * section 6.4.1, and for a `HEAD` request.
     *
     * Subclasses override the method to resolve the response according to the request, which is
     * `null` when the response is serialized, or sent, without one. In that case they must resolve
     * to what the response would be for a plain `GET` request. They must not modify the response.
     */
    public function finalize(?Request $request = null): FinalResponse
    {
        $body = $this->body;
        $code = $this->status->code;

        if (
            $code < 200
            || $code === self::STATUS_NO_CONTENT
            || $code === self::STATUS_NOT_MODIFIED
            || $request?->method->is_head()
        ) {
            $body = null;
        } elseif ($body instanceof Stringable) {
            $body = (string) $body;
        }

        return new FinalResponse($this->version, clone $this->status, clone $this->headers, $body);
    }

    /**
     * Renders the response as an HTTP string.
     *
     * The response is finalized without a request, see {@see finalize()}.
     */
    public function __toString(): string
    {
        return (string) $this->finalize();
    }

    /**
     * Sends the response using a {@see SimpleResponseSender}.
     *
     * The body is not sent when the finalized body is `null`, which is the case for the statuses
     * that cannot have a body (`1xx`, `204`, and `304`), and when the request is a `HEAD`
     * request, see {@see finalize()}.
     *
     * @param Request|null $request The request is used by responses that finalize themselves
     * according to it, such as {@see FileResponse}.
     *
     * @deprecated Use a {@see ResponseSender}.
     */
    public function __invoke(?Request $request = null): void
    {
        (new SimpleResponseSender())->send($this->finalize($request));
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

            if (is_numeric($age)) {
                return (int)$age;
            }

            $timestamp = $this->headers->date->timestamp;

            return $timestamp === null ? null : max(0, time() - $timestamp);
        }

        set {
            $this->headers['Age'] = $value;
        }
    }

    /**
     * The `Expires` header field.
     *
     * Setting this property also sets the `max-age` directive of the `Cache-Control` header field to the
     * number of seconds until the expiration, or removes it if the date is empty.
     */
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
     *
     * It is computed from the `s-maxage` directive of the `Cache-Control` header, or `max-age` if
     * `s-maxage` is not defined, minus the age of the response. Setting this property adjusts
     * the `s-maxage` directive according to the age, so that reading it returns the value that was
     * set. Setting it to `null` removes the directive.
     *
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * re-validating with the origin.
     */
    public ?int $ttl {
        get {
            $cache_control = $this->headers->cache_control;
            $max_age = $cache_control->s_maxage ?? $cache_control->max_age;

            return $max_age === null ? null : $max_age - ($this->age ?? 0);
        }

        set {
            $this->headers->cache_control->s_maxage = $value === null ? null : ($this->age ?? 0) + $value;
        }
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
                    || $this->headers->cache_control->cacheable === 'private'
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
