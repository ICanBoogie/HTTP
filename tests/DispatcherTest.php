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

use ICanBoogie\Events;

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

		$dispatcher = new Dispatcher;
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

		$dispatcher = new Dispatcher([

			'exception' => function() {

				throw new \Exception('Damned!');

			}

		]);

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
		$dispatcher = new Dispatcher;
		$dispatcher(Request::from($_SERVER));
	}

	public function testSetDispatchersWeight()
	{
		$dispatcher = new Dispatcher([

			'two' => 'dummy',
			'three' => 'dummy'

		]);

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

	public function test_head_fallback()
	{
		$message = "Astonishing success!";

		$dispatcher = new Dispatcher([

			'primary' => function(Request $request) use($message) {

				if ($request->is_get)
				{
					if ($request->uri == '/with-message')
					{
						return new Response($message, 200, [ 'X-Was-Get' => 'yes' ]);
					}
					else
					{
						return new Response(null, 200, [ 'X-Was-Get' => 'yes', 'Content-Length' => 1234 ]);
					}
				}

			}

		]);

		$request = Request::from([

			'uri' => '/with-message',
			'is_head' => true

		]);

		$response = $dispatcher($request);

		$this->assertInstanceOf('ICanBoogie\HTTP\Response', $response);
		$this->assertEquals(200, $response->status);
		$this->assertEquals(strlen($message), $response->content_length);
		$this->assertEquals('yes', $response->headers['X-Was-Get']);
		$this->assertNull($response->body);

		$request = Request::from([

			'is_head' => true

		]);

		$response = $dispatcher($request);
		$this->assertEquals(1234, $response->content_length);
	}
}
