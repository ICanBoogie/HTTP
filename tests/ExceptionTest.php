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

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_implements
	 */
	public function test_implements($class, $args)
	{
		$reflection = new \ReflectionClass(__NAMESPACE__ . '\\' . $class);
		$exception = $reflection->newInstanceArgs($args);

		$this->assertInstanceOf(Exception::class, $exception);
	}

	public function provide_test_implements()
	{
		return [

			[ 'NotFound', [] ],
			[ 'ServiceUnavailable', [] ],
			[ 'MethodNotSupported', [ 'UNSUPPORTED' ] ],
			[ 'StatusCodeNotValid', [ 123 ] ],
			[ 'ForceRedirect', [ 'to/location.html' ] ]

		];
	}
}
