<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Tests\HTTP\Dispatcher;

use ICanBoogie\HTTP\WeightedDispatcher;

use ICanBoogie\Events;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
	static private $events;

	static public function setUpBeforeClass()
	{
		self::$events = $events = new Events;

		Events::patch('get', function() use($events) { return $events; });
	}

	/**
	 * The event hooks for the `ICanBoogie\HTTP\Dispatcher::dispatch:before` and
	 * `ICanBoogie\HTTP\Dispatcher::dispatch` events must be called and a response must be
	 * provided with the body 'Ok'.
	 */
	public function testDispatchEvent()
	{
		$before_done = null;
		$done = null;

		$beh = self::$events->attach(function(Dispatcher\BeforeDispatchEvent $event, Dispatcher $target) use(&$before_done) {

			$before_done = true;

		});

		$eh = self::$events->attach(function(Dispatcher\DispatchEvent $event, Dispatcher $target) use(&$done) {

			$done = true;

			$event->response = new Response('Ok');

		});

		$dispatcher = new Dispatcher();
		$response = $dispatcher(Request::from($_SERVER));

		$this->assertTrue($before_done);
		$this->assertTrue($done);
		$this->assertEquals('Ok', $response->body);

		$beh->detach();
		$eh->detach();
	}

	/**
	 * The exception thrown by the _exception_ dispatcher must be rescued by the _rescue event hook_.
	 */
	public function testDispatcherRescueEvent()
	{
		$eh = self::$events->attach(function(\ICanBoogie\Exception\RescueEvent $event, \Exception $target) {

			$event->response = new Response("Rescued: " . $event->exception->getMessage());

		});

		$dispatcher = new Dispatcher
		(
			array
			(
				'exception' => function() {

					throw new \Exception('Damned!');

				}
			)
		);

		$response = $dispatcher(Request::from($_SERVER));

		$this->assertInstanceOf('ICanBoogie\HTTP\Response', $response);
		$this->assertEquals('Rescued: Damned!', $response->body);

		$eh->detach();
	}

	/**
	 * @expectedException ICanBoogie\HTTP\NotFound
	 */
	public function testNotFound()
	{
		$dispatcher = new Dispatcher();
		$dispatcher(Request::from($_SERVER));
	}

	public function testSetDispatchersWeight()
	{
		$dispatcher = new Dispatcher(array(

			'two' => 'dummy',
			'three' => 'dummy'

		));

		$dispatcher['bottom'] = new WeightedDispatcher('dummy', 'bottom');
		$dispatcher['megabottom'] = new WeightedDispatcher('dummy', 'bottom');
		$dispatcher['hyperbottom'] = new WeightedDispatcher('dummy', 'bottom');
		$dispatcher['one'] = new WeightedDispatcher('dummy', 'before:two');
		$dispatcher['four'] = new WeightedDispatcher('dummy', 'after:three');
		$dispatcher['top'] = new WeightedDispatcher('dummy', 'top');
		$dispatcher['megatop'] = new WeightedDispatcher('dummy', 'top');
		$dispatcher['hypertop'] = new WeightedDispatcher('dummy', 'top');

		$order = '';

		foreach ($dispatcher as $dispatcher_id => $dummy)
		{
			$order .= ' ' . $dispatcher_id;
		}

		$this->assertSame(' hypertop megatop top one two three four bottom megabottom hyperbottom', $order);
	}
}