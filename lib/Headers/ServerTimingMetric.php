<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers;
use InvalidArgumentException;
use Stringable;

use function addcslashes;
use function number_format;
use function preg_match;
use function rtrim;

/**
 * A metric of the `Server-Timing` header field.
 *
 * @link https://www.w3.org/TR/server-timing/#the-server-timing-header-field
 */
final class ServerTimingMetric implements Stringable
{
    /**
     * The name of the metric, a token.
     *
     * @var non-empty-string
     */
    public string $name {
        set {
            if (!preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $value)) {
                throw new InvalidArgumentException("Invalid server timing metric name: '$value'.");
            }

            $this->name = $value;
        }
    }

    /**
     * The duration of the metric, in milliseconds.
     */
    public ?float $duration {
        set {
            if ($value !== null && $value < 0) {
                throw new InvalidArgumentException(
                    "The duration of a server timing metric cannot be negative: $value."
                );
            }

            $this->duration = $value;
        }
    }

    /**
     * The description of the metric.
     *
     * @var non-empty-string|null
     */
    public ?string $description {
        set {
            if ($value !== null) {
                Headers::assert_value_is_safe('description', $value);
            }

            $this->description = $value;
        }
    }

    /**
     * @param non-empty-string $name
     * @param float|null $duration
     * @param non-empty-string|null $description
     */
    public function __construct(string $name, ?float $duration = null, ?string $description = null)
    {
        $this->name = $name;
        $this->duration = $duration;
        $this->description = $description;
    }

    /**
     * Renders the metric, e.g. `cache;dur=23.2;desc="Cache Read"`.
     *
     * The duration is rounded to 2 decimals, a hundredth of a millisecond is finer than what the
     * clients display or what a measure is worth.
     */
    public function __toString(): string
    {
        $rendered = $this->name;

        if ($this->duration !== null) {
            $rendered .= ';dur=' . rtrim(rtrim(number_format($this->duration, 2, '.', ''), '0'), '.');
        }

        if ($this->description !== null) {
            $rendered .= ';desc="' . addcslashes($this->description, '"\\') . '"';
        }

        return $rendered;
    }
}
