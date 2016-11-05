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

class DateTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provider_test_to_string
	 *
	 * @param string $expected
	 * @param mixed $datetime
	 */
	public function test_to_string($expected, $datetime)
	{
		$field = Date::from($datetime);

		$this->assertEquals($expected, (string) $field);
	}

	public function provider_test_to_string()
	{
		$berlin = new \DateTimeZone('Europe/Berlin');
		$now = new \DateTime('now', $berlin);
		$nowImmutable = new \DateTimeImmutable('now', $berlin);

		return [

			[ Date::to_rfc1123($now), $now ],
			[ Date::to_rfc1123($nowImmutable), $nowImmutable ],
			[ '', new \DateTime('0000-00-00') ],
			[ '', new \DateTimeImmutable('0000-00-00') ],
			[ '', null ]

		];
	}
}
