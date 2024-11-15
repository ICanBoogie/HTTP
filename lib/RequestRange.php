<?php

namespace ICanBoogie\HTTP;

use function preg_match;
use function sprintf;

/**
 * Representation of a request range.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Range
 */
readonly class RequestRange
{
    /**
     * Creates a new instance.
     *
     * @return RequestRange|null A new instance, or `null` if the range is not defined or deprecated
     * (because `If-Range` doesn't match `$etag`).
     */
    public static function from(Headers $headers, int $total, string $etag): ?self
    {
        $range = (string) $headers[Headers::HEADER_RANGE];

        if (!$range) {
            return null;
        }

        $if_range = (string) $headers[Headers::HEADER_IF_RANGE];

        if ($if_range && $if_range !== $etag) {
            return null;
        }

        $range = self::resolve_range($range, $total);

        if (!$range) {
            return null;
        }

        return new self($range[0], $range[1], $total);
    }

    /**
     * Resolves the range.
     *
     * @return array{ 0: int, 1: int }|null An array with `[ $start, $end ]`, or `null` if the range is invalid.
     */
    private static function resolve_range(string $range, int $total): ?array
    {
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches)) {
            return null;
        }

        [ , $start, $end ] = $matches;

        if ($start === '' && $end === '') {
            return null;
        }

        $end = $end === '' ? $total - 1 : (int) $end;

        if ($start === '') {
            $start = $total - $end;
            $end = $total - 1;
        } else {
            $start = (int) $start;
        }

        return [ $start, $end ];
    }

    /**
     * @var int The offset where to start to copy data, suitable for the `stream_copy_to_stream()` function.
     */
    public int $offset;

    /**
     * @var int Length of the range, suitable for the `Content-Length` header field.
     */
    public int $length;

    /**
     * @var int Maximum bytes to copy, suitable for the `stream_copy_to_stream()` function.
     */
    public int $max_length;

    /**
     * @var bool Whether the range is satisfiable.
     */
    public bool $is_satisfiable;

    /**
     * @var bool Whether the range is actually the total.
     */
    public bool $is_total;

    protected function __construct(
        private int $start,
        private int $end,
        private int $total
    ) {
        $this->offset = $start;
        $this->length = $length = $this->end - $this->start + 1;
        $this->max_length = $end < $total ? $length : -1;
        $this->is_satisfiable = !($start < 0 || $start >= $end || $end > $total - 1);
        $this->is_total = $start === 0 && $end === $total - 1;
    }

    /**
     * Formats the instance as a string suitable for the `Content-Range` header field.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Range
     */
    public function __toString(): string
    {
        return sprintf('bytes %s-%s/%s', $this->start, $this->end, $this->total);
    }
}
