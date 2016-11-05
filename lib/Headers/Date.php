<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\PropertyNotDefined;

/**
 * A date time object that renders into a string formatted for HTTP header fields.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
 *
 * @property-read bool $is_empty
 * @property-read int $timestamp
 */
class Date extends \DateTimeImmutable
{
    /**
     * @param mixed $source
     * @param \DateTimeZone|null $timezone
     *
     * @return Date
     */
	static public function from($source, $timezone = null)
	{
		if ($source === null)
		{
			return self::none();
		}

		return new static($source, $timezone);
	}

	/**
	 * @return Date
	 */
	static public function none()
	{
		return new static('0000-00-00', self::utc());
	}

	/**
	 * @return \DateTimeZone
	 */
	static private function utc()
	{
		static $utc;

		return $utc ?: $utc = new \DateTimeZone('UTC');
	}

	/**
	 * @param \DateTimeInterface $datetime
	 *
	 * @return string
	 */
	static public function to_rfc1123(\DateTimeInterface $datetime)
	{
		$utc = self::utc();

		if ($datetime->getTimezone()->getName() != $utc->getName())
		{
			$datetime = new \DateTime($datetime->format(\DateTime::ATOM));
			$datetime = $datetime->setTimezone($utc);
		}

		$str = $datetime->format(\DateTime::RFC1123);
		$str = str_replace(' +0000', ' GMT', $str);

		return $str;
	}

	/**
	 * @param string|int|\DateTimeInterface $time If time is provided as a numeric value it is used as
	 * "@{$time}" and the time zone is set to UTC.
	 * @param \DateTimeZone|string $timezone A {@link \DateTimeZone} object representing the desired
	 * time zone. If the time zone is empty `utc` is used instead.
	 */
	public function __construct($time = 'now', $timezone = null)
	{
		if ($time instanceof \DateTimeInterface)
		{
			$time = $time->getTimestamp();
		}

		if (is_numeric($time))
		{
			$time = '@' . $time;
			$timezone = null;
		}

		if (is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}

		parent::__construct($time, $timezone ?: self::utc());
	}

	/**
	 * Formats the instance according to the RFC 1123.
     *
     * @inheritdoc
	 */
	public function __toString()
	{
		if ($this->is_empty)
		{
			return '';
		}

		return self::to_rfc1123($this);
	}

	public function __get($property)
	{
		switch($property)
		{
			case 'is_empty':
				return $this->format('Y') == -1 && $this->format('m') == 11 && $this->format('d') == 30;
				break;

			case 'timestamp':
				return $this->getTimestamp();
				break;
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}
}
