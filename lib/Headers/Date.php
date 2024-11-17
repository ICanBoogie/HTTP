<?php

namespace ICanBoogie\HTTP\Headers;

use DateTimeZone;
use ICanBoogie\DateTime;

/**
 * A date time object that renders into a string formatted for HTTP header fields.
 *
 * @property-read bool $is_empty
 *     Whether the value of the {@see Date} is empty.
 * @property-read int $timestamp
 *     The Unix timestamp in seconds.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
 */
class Date extends DateTime
{
    public static function from(
        self|\DateTimeInterface|int|string|null $source,
        DateTimeZone|string|null $timezone = null,
    ): static {
        if ($source === null) {
            $timezone = 'UTC';
            $source = '0000-00-00';
        } elseif ($source instanceof self) {
            // @phpstan-ignore-next-line
            return clone $source;
        } elseif ($source instanceof \DateTimeInterface) {
            $timezone = $source->getTimezone();
            $source = $source->format('Y-m-d\TH:i:s.u');
        } elseif (is_int($source)) {
            $timezone = 'UTC';
            $source = "@{$source}";
        }

        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }

        // @phpstan-ignore-next-line
        return new self($source, $timezone);
    }

    private function __construct(string $time, ?DateTimeZone $timezone = null)
    {
        parent::__construct($time, $timezone);
    }

    /**
     * Formats the instance according to the RFC 1123.
     */
    public function __toString(): string
    {
        return $this->is_empty
            ? ''
            : str_replace('+0000', 'GMT', $this->format(\DateTimeInterface::RFC1123));
    }

    /**
     * The timestamp of a {@see \DateTime} or {@see \DateTimeImmutable} created with "0000-00-00".
     */
    private const EMPTY_TIMESTAMP = -62169984000;

    public function __get($property)
    {
        return match ($property) {
            'is_empty' => $this->timestamp == self::EMPTY_TIMESTAMP,
            'timestamp' => parent::getTimestamp(),
            default => throw new \BadMethodCallException('Undefined property: ' . get_class($this) . '::' . $property),
        };
    }
}
