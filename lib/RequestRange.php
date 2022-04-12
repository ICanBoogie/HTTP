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

use ICanBoogie\Accessor\AccessorTrait;

use function preg_match;

/**
 * Representation of a request range.
 *
 * @property bool $is_satisfiable Whether the range is satisfiable.
 * @property bool $is_total Whether the range is actually the total.
 * @property int $length Length of the range, suitable for the `Content-Length` header field.
 * @property int $max_length Maximum bytes to copy, suitable for the `stream_copy_to_stream()`
 *     function.
 * @property int $offset The offset where to start to copy data, suitable for the
 *     `stream_copy_to_stream()` function.
 */
class RequestRange
{
    use AccessorTrait;

    /**
     * Creates a new instance.
     *
     * @param Headers $headers
     * @param int $total
     * @param string $etag
     *
     * @return RequestRange|null A new instance, or `null` if the range is not defined or deprecated
     * (because `If-Range` doesn't match `$etag`).
     */
    public static function from(Headers $headers, int $total, string $etag): ?self
    {
        $range = (string) $headers['Range'];

        if (!$range) {
            return null;
        }

        $if_range = (string) $headers['If-Range'];

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
     * @return array|null An array with `[ $start, $end ]`, or `null` if the range is invalid.
     */
    private static function resolve_range(string $range, int $total)
    {
        if (!preg_match('/^bytes\=(\d*)\-(\d*)$/', $range, $matches)) {
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
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $end;

    /**
     * @var int
     */
    private $total;

    /**
     * @param int $start
     * @param int $end
     * @param int $total
     */
    protected function __construct(int $start, int $end, int $total)
    {
        $this->start = $start;
        $this->end = $end;
        $this->total = $total;
    }

    /**
     * Formats the instance as a string suitable for the `Content-Range` header field.
     *
     * @return string
     */
    public function __toString()
    {
        return \sprintf('bytes %s-%s/%s', $this->start, $this->end, $this->total);
    }

    /**
     * Whether the range is satisfiable.
     *
     * @return bool
     */
    protected function get_is_satisfiable(): bool
    {
        $start = $this->start;
        $end = $this->end;

        return !($start < 0 || $start >= $end || $end > $this->total - 1);
    }

    /**
     * Whether the range is actually the total.
     *
     * @return bool
     */
    protected function get_is_total(): bool
    {
        return $this->start === 0 && $this->end === $this->total - 1;
    }

    /**
     * Returns the length of the range, suitable for the `Content-Length` header field.
     *
     * @return int
     */
    protected function get_length(): int
    {
        return $this->end - $this->start + 1;
    }

    /**
     * Maximum bytes to copy, suitable for the `stream_copy_to_stream()` function.
     *
     * @return int
     */
    protected function get_max_length(): int
    {
        return $this->end < $this->total ? $this->length : -1;
    }

    /**
     * Returns the offset where to start to copy data, suitable for the
     * `stream_copy_to_stream()` function.
     *
     * @return int
     */
    protected function get_offset(): int
    {
        return $this->start;
    }
}
