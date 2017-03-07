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

class DispatcherProviderTest extends \PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		DispatcherProvider::undefine();
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\DispatcherProviderNotDefined
	 */
	public function test_should_throw_exception_when_no_provider_is_defined()
	{
		DispatcherProvider::provide();
	}

	public function test_define()
	{
		$provider1 = function() { };
		$provider2 = function() { };

		$this->assertNull(DispatcherProvider::defined());
		$this->assertNull(DispatcherProvider::define($provider1));
		$this->assertSame($provider1, DispatcherProvider::define($provider2));
		$this->assertSame($provider2, DispatcherProvider::defined());

		DispatcherProvider::undefine();
		$this->assertNull(DispatcherProvider::define($provider1));
	}

	public function test_provide()
	{
		$dispatcher = $this->getMockBuilder(Dispatcher::class)
			->getMock();

		DispatcherProvider::define(function() use ($dispatcher) {

			return $dispatcher;

		});

		$this->assertSame($dispatcher, DispatcherProvider::provide());
		$this->assertSame($dispatcher, get_dispatcher());
	}
}
