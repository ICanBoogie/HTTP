<?php

namespace ICanBoogie\HTTP\Headers;

use DateTimeInterface;
use DateTimeZone;
use ICanBoogie\DateTime;

use function is_numeric;

/**
 * A date time object that renders into a string formatted for HTTP header fields.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
 */
class Date extends DateTime
{
    public static function from(
        self|\DateTimeInterface|string|null $source,
        DateTimeZone|string|null $timezone = null
    ): static {
        if ($source === null) {
            return static::none();
        }

        return parent::from($source, $timezone);
    }

    /**
     * @param string|int|DateTimeInterface $time If time is provided as a numeric value it is used
     *     as
     * "@{$time}" and the time zone is set to UTC.
     * @param DateTimeZone|string $timezone A {@link \DateTimeZone} object representing the desired
     * time zone. If the time zone is empty `utc` is used instead.
     */
    public function __construct($time = 'now', $timezone = null)
    {
        if ($time instanceof DateTimeInterface) {
            $time = $time->getTimestamp();
        }

        if (is_numeric($time)) {
            $time = '@' . $time;
            $timezone = null;
        }

        parent::__construct($time, $timezone ?: 'utc');
    }

    /**
     * Formats the instance according to the RFC 1123.
     *
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->is_empty ? '' : $this->utc->as_rfc1123;
    }
}
