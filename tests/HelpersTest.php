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

class HelpersTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \LogicException
	 */
	public function test_patch_undefined()
	{
		Helpers::patch(uniqid(), function() {});
	}

	public function test_get_dispatcher()
	{
		$dispatcher = get_dispatcher();

		$this->assertInstanceOf('ICanBoogie\HTTP\Dispatcher', $dispatcher);
		$this->assertSame($dispatcher, get_dispatcher());
	}

	public function test_dispatch()
	{
		$request = $this
			->getMockBuilder('ICanBoogie\HTTP\Request')
			->disableOriginalConstructor()
			->getMock();

		$response = $this
			->getMockBuilder('ICanBoogie\HTTP\Response')
			->disableOriginalConstructor()
			->getMock();

		$dispatcher = $this
			->getMockBuilder('ICanBoogie\HTTP\Dispatcher')
			->disableOriginalConstructor()
			->setMethods([ '__invoke' ])
			->getMock();
		$dispatcher
			->expects($this->once())
			->method('__invoke')
			->with($request)
			->willReturn($response);

		/* @var $request Request */

		$previous_get_dispatcher = Helpers::patch('get_dispatcher', function() use ($dispatcher) {

			return $dispatcher;

		});

		$this->assertSame($response, dispatch($request));

		Helpers::patch('get_dispatcher', $previous_get_dispatcher);
	}

	public function test_get_initial_request()
	{
		$request = get_initial_request();

		$this->assertInstanceOf('ICanBoogie\HTTP\Request', $request);
		$this->assertSame($request, get_initial_request());
	}
}
