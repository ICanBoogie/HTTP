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

use ICanBoogie\DateTime;

class DateTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provider_test_to_string
	 */
	public function test_to_string($expected, $datetime)
	{
		$field = Date::from($datetime);

		$this->assertEquals($expected, (string) $field);
	}

	public function provider_test_to_string()
	{
		$now = DateTime::now('Europe/Berlin');

		return [

			[ $now->utc->as_rfc1123, $now ],
			[ '', DateTime::none() ],
			[ '', null ]

		];
	}
}
