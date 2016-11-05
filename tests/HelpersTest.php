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

class HelpersTest extends \PHPUnit\Framework\TestCase
{
	public function test_dispatcher()
	{
		$dispatcher = get_dispatcher();
		$this->assertInstanceOf(RequestDispatcher::class, $dispatcher);
		$this->assertSame($dispatcher, get_dispatcher());

		$other_dispatcher = $this->getMockBuilder(Dispatcher::class)
			->getMock();

		DispatcherProvider::define(function() use ($other_dispatcher) {

			return $other_dispatcher;

		});

		$this->assertSame($other_dispatcher, get_dispatcher());

		DispatcherProvider::undefine();
	}
}
