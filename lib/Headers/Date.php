<?php

namespace ICanBoogie\HTTP\Headers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Representation of a 'Date' header field.
 *
 * @property-read bool $is_empty
 *     Whether the value of the {@see Date} is empty.
 * @property-read int|null $timestamp
 *     The Unix timestamp in seconds, or null if {@see Date} is empty.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
 */
readonly class Date
{
    public static function from(
        DateTimeInterface|int|string|null $source
    ): self {
        $timezone = null;

        if ($source === null) {
            return new self();
        } elseif ($source instanceof DateTimeInterface) {
            $timezone = $source->getTimezone();
            $source = $source->format('Y-m-d\TH:i:s.u');
        } elseif (is_int($source)) {
            $timezone = 'UTC';
            $source = "@{$source}";
        }

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        $datetime = new DateTimeImmutable($source, $timezone);

        return new self($datetime);
    }

    private function __construct(
        public ?DateTimeInterface $delegate = null
    ) {
    }

    /**
     * Formats the instance according to the RFC 1123.
     */
    public function __toString(): string
    {
        return $this->is_empty
            ? ''
            : str_replace('+0000', 'GMT', $this->delegate->format(DateTimeInterface::RFC1123));
    }

    /**
     * The timestamp of a {@see \DateTime} or {@see DateTimeImmutable} created with "0000-00-00".
     */
    private const EMPTY_TIMESTAMP = -62169984000;

    public function __get($property)
    {
        return match ($property) {
            'is_empty' => $this->delegate === null || $this->timestamp == self::EMPTY_TIMESTAMP,
            'timestamp' => $this->delegate?->getTimestamp(),
            default => throw new \BadMethodCallException('Undefined property: ' . get_class($this) . '::' . $property),
        };
    }
}
