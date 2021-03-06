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

class PermissionRequiredTest extends \PHPUnit\Framework\TestCase
{
	public function test_message()
	{
		$exception = new PermissionRequired;

		$this->assertEquals("You don't have the required permission.", $exception->getMessage());
		$this->assertEquals(Status::UNAUTHORIZED, $exception->getCode());
	}
}
