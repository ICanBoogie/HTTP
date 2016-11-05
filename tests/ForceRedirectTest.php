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

class ForceRedirectTest extends \PHPUnit\Framework\TestCase
{
	public function test_get_location()
	{
		$location = '/to/location.html';
		$exception = new ForceRedirect($location);
		$this->assertEquals(Status::FOUND, $exception->getCode());
		$this->assertEquals($location, $exception->location);
	}
}
