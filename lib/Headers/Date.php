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

/**
 * A date time object that renders into a string formatted for HTTP header fields.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
 */
class Date extends \ICanBoogie\DateTime
{
	static public function from($source, $timezone=null)
	{
		if ($source === null)
		{
			return static::none();
		}

		return parent::from($source, $timezone);
	}

	/**
	 * Constructor.
	 *
	 * @param string|int|\DateTime $time If time is provided as a numeric value it is used as
	 * "@{$time}" and the time zone is set to UTC.
	 * @param \DateTimeZone|string $timezone A {@link \DateTimeZone} object representing the desired
	 * time zone. If the time zone is empty `utc` is used instead.
	 */
	public function __construct($time='now', $timezone=null)
	{
		if ($time instanceof \DateTime)
		{
			$time = $time->getTimestamp();
		}

		if (is_numeric($time))
		{
			$time = '@' . $time;
			$timezone = null;
		}

		parent::__construct($time, $timezone ?: 'utc');
	}

	/**
	 * Formats the instance according to the RFC 1123.
	 */
	public function __toString()
	{
		return $this->is_empty ? '' : $this->utc->as_rfc1123;
	}
}
