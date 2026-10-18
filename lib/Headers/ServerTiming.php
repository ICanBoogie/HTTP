<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers;
use InvalidArgumentException;
use Stringable;

use function array_shift;
use function count;
use function explode;
use function implode;
use function is_numeric;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function strtolower;
use function trim;

/**
 * Representation of the `Server-Timing` header field, a list of metrics.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Headers\ServerTiming;
 *
 * $timing = new ServerTiming();
 * $timing->add('db', 53.0);
 * $timing->add('cache', 23.2, 'Cache Read');
 *
 * echo $timing; // db;dur=53, cache;dur=23.2;desc="Cache Read"
 * </pre>
 *
 * @link https://www.w3.org/TR/server-timing/
 */
final class ServerTiming implements Stringable
{
    /**
     * Creates a {@see ServerTiming} instance from the provided source.
     *
     * @param string|ServerTiming|null $source The source to create the instance from. If the source is an
     * instance of {@see ServerTiming} it is returned as is.
     *
     * @throws InvalidArgumentException if the source is malformed.
     */
    public static function from(string|self|null $source): self
    {
        if ($source instanceof self) {
            return $source;
        }

        $timing = new self();

        if ($source === null) {
            return $timing;
        }

        Headers::assert_value_is_safe(Headers::HEADER_SERVER_TIMING, $source);

        foreach (self::split(',', $source) as $member) {
            $member = trim($member);

            if ($member === '') {
                continue;
            }

            $metric = self::parse_metric($member);

            if ($metric !== null) {
                $timing->metrics[] = $metric;
            }
        }

        return $timing;
    }

    /**
     * Splits a string on a separator, except within quoted strings.
     *
     * @return list<string>
     */
    private static function split(string $separator, string $source): array
    {
        preg_match_all(
            '/(?:[^' . $separator . '"]++|"(?:[^"\\\\]++|\\\\.)*+"?)++/s',
            $source,
            $matches
        );

        return $matches[0];
    }

    /**
     * Parses a metric following the processing rules of the specification: parameters that are
     * malformed, unknown, or repeated are ignored, only the first instance of a parameter is
     * considered, even if it is invalid, and a metric without a name is discarded.
     *
     * @param non-empty-string $source
     *
     * @throws InvalidArgumentException if the name of the metric is not a token, or if its duration
     * is negative, which the specification doesn't forbid but makes no sense.
     */
    private static function parse_metric(string $source): ?ServerTimingMetric
    {
        if ($source[0] === ';') {
            return null;
        }

        $parameters = self::split(';', $source);
        $metric = new ServerTimingMetric(trim(array_shift($parameters)));
        $seen = [];

        foreach ($parameters as $parameter) {
            $parts = explode('=', $parameter, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $attribute = strtolower(trim($parts[0]));

            if ($attribute === '' || isset($seen[$attribute])) {
                continue;
            }

            $seen[$attribute] = true;
            $value = ltrim($parts[1]);

            if ($value !== '' && $value[0] === '"') {
                // Anything after the closing quote is extraneous and ignored.
                preg_match('/^"((?:[^"\\\\]|\\\\.)*)/s', $value, $matches);
                $value = preg_replace('/\\\\(.)/s', '$1', $matches[1]);
            } else {
                $value = rtrim($value);
            }

            switch ($attribute) {
                case 'dur':
                    $metric->duration = is_numeric($value) ? (float)$value : null;
                    break;

                case 'desc':
                    $metric->description = $value;
                    break;
            }
        }

        return $metric;
    }

    /**
     * @var list<ServerTimingMetric>
     */
    public array $metrics = [];

    /**
     * Adds a metric.
     *
     * @param non-empty-string $name
     * @param float|null $duration The duration in milliseconds.
     * @param non-empty-string|null $description
     *
     * @throws InvalidArgumentException if the name is not a token, or the duration is negative.
     */
    public function add(
        string $name,
        ?float $duration = null,
        ?string $description = null
    ): ServerTimingMetric {
        return $this->metrics[] = new ServerTimingMetric($name, $duration, $description);
    }

    public function __toString(): string
    {
        return implode(', ', $this->metrics);
    }
}
