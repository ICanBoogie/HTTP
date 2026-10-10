<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use DateTimeImmutable;
use DateTimeZone;

use function max;
use function min;
use function preg_match;
use function sprintf;

/**
 * Representation of a request range.
 *
 * Only single ranges are supported. A request with multiple ranges, such as `bytes=0-99,200-299`,
 * is treated as a request without range, and the whole file is served.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9110#name-range-requests
 */
readonly class RequestRange
{
    /**
     * Creates a new instance.
     *
     * @param int $total The size of the representation.
     * @param string $etag The entity tag of the representation, compared to `If-Range`.
     * @param int|null $last_modified The modification time of the representation, compared to
     *     `If-Range` when it is a date.
     *
     * @return RequestRange|null A new instance, or `null` if the range is not defined, invalid,
     * or deprecated (because `If-Range` doesn't match).
     */
    public static function from(Headers $headers, int $total, string $etag, ?int $last_modified = null): ?self
    {
        $range = (string) $headers[Headers::HEADER_RANGE];

        if (!$range) {
            return null;
        }

        if (!self::if_range_matches((string) $headers[Headers::HEADER_IF_RANGE], $etag, $last_modified)) {
            return null;
        }

        $range = self::resolve_range($range, $total);

        if (!$range) {
            return null;
        }

        return new self($range[0], $range[1], $total);
    }

    /**
     * Whether `If-Range` matches the representation.
     *
     * An entity tag must match exactly, a date must be equal to the modification time.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#name-if-range
     */
    private static function if_range_matches(string $if_range, string $etag, ?int $last_modified): bool
    {
        if ($if_range === '' || $if_range === $etag) {
            return true;
        }

        if ($last_modified === null) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $if_range, new DateTimeZone('UTC'));

        return $date !== false && $date->getTimestamp() === $last_modified;
    }

    /**
     * Resolves the range.
     *
     * A last position beyond the end of the representation is clamped, and so is a suffix longer
     * than the representation. A range that starts beyond the end is returned as is, it is not
     * satisfiable.
     *
     * @return array{ 0: int, 1: int }|null An array with `[ $start, $end ]`, or `null` if the range is invalid.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#name-byte-ranges
     */
    private static function resolve_range(string $range, int $total): ?array
    {
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches)) {
            return null;
        }

        [ , $first, $last ] = $matches;

        if ($first === '' && $last === '') {
            return null;
        }

        if ($first === '') {
            // A suffix of 0 starts at $total, which makes the range unsatisfiable.
            return [ max(0, $total - (int) $last), $total - 1 ];
        }

        $start = (int) $first;

        if ($last === '') {
            return [ $start, $total - 1 ];
        }

        $end = (int) $last;

        if ($end < $start) {
            return null;
        }

        return [ $start, min($end, $total - 1) ];
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
        $this->length = $this->max_length = max(0, $end - $start + 1);
        $this->is_satisfiable = $start < $total && $end >= $start;
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
