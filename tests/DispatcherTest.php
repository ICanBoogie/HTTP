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

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;

class DispatcherTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var EventCollection
	 */
	private $events;

	protected function setUp(): void
	{
		$this->events = $events = new EventCollection;

		EventCollectionProvider::define(function() use ($events) {

			return $events;

		});
	}

	/**
	 * The event hooks for the `ICanBoogie\HTTP\RequestDispatcher::dispatch:before` and
	 * `ICanBoogie\HTTP\RequestDispatcher::dispatch` events must be called and a response must be
	 * provided with the body 'Ok'.
	 */
	public function testDispatchEvent()
	{
		$before_done = null;
		$done = null;

		$this->events->attach(function(RequestDispatcher\BeforeDispatchEvent $event, RequestDispatcher $target) use(&$before_done) {

			$before_done = true;

		});

		$this->events->attach(function(RequestDispatcher\DispatchEvent $event, RequestDispatcher $target) use(&$done) {

			$done = true;

			$event->response = new Response('Ok');

		});

		$dispatcher = new RequestDispatcher;
		$response = $dispatcher(Request::from($_SERVER));

		$this->assertTrue($before_done);
		$this->assertTrue($done);
		$this->assertEquals('Ok', $response->body);
	}

	/**
	 * The exception thrown by the _exception_ dispatcher must be rescued by the _rescue event hook_.
	 */
	public function testDispatcherRescueEvent()
	{
		$this->events->attach(function(\ICanBoogie\Exception\RescueEvent $event, \Exception $target) {

			$event->response = new Response("Rescued: " . $event->exception->getMessage());

		});

		$dispatcher = new RequestDispatcher([

			'exception' => function() {

				throw new \Exception('Damned!');

			}

		]);

		$response = $dispatcher(Request::from($_SERVER));

		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals('Rescued: Damned!', $response->body);
	}

	public function testNotFound()
	{
		$dispatcher = new RequestDispatcher;
		$this->expectException(NotFound::class);
		$dispatcher(Request::from($_SERVER));
	}

	public function testSetDispatchersWeight()
	{
		$dispatcher = new RequestDispatcher([

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

		$this->assertSame(
		    'hypertop megatop top one two three four bottom megabottom hyperbottom',
            implode(' ', array_keys(iterator_to_array($dispatcher)))
        );
	}

	public function test_head_fallback()
	{
		$message = "Astonishing success!";

		$dispatcher = new RequestDispatcher([

			'primary' => function(Request $request) use($message) {

				if ($request->is_get)
				{
					if ($request->uri == '/with-message')
					{
						return new Response($message, Status::OK, [ 'X-Was-Get' => 'yes' ]);
					}
					else
					{
						return new Response(null, Status::OK, [ 'X-Was-Get' => 'yes', 'Content-Length' => 1234 ]);
					}
				}

				return null;

			}

		]);

		$request = Request::from([

			Request::OPTION_URI => '/with-message',
			Request::OPTION_IS_HEAD => true

		]);

		$response = $dispatcher($request);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertInstanceOf(Status::class, $response->status);
		$this->assertEquals(Status::OK, $response->status->code);
		$this->assertEquals(strlen($message), $response->content_length);
		$this->assertEquals('yes', $response->headers['X-Was-Get']);
		$this->assertNull($response->body);

		$request = Request::from([

			Request::OPTION_IS_HEAD => true

		]);

		$response = $dispatcher($request);
		$this->assertEquals(1234, $response->content_length);
	}

	public function test_head_strip_body()
	{
		$original_response = null;
		$dispatcher = new RequestDispatcher([

			'primary' => function(Request $request) use(&$original_response) {

				return $original_response = new Response("With a fantastic message!");

			}

		]);

		$request = Request::from([

			Request::OPTION_IS_HEAD => true

		]);

		$response = $dispatcher($request);
		$expected = "HTTP/1.1 200 OK\r\nDate: {$response->date}\r\n\r\n";

		$this->assertInstanceOf(Response::class, $response);
		$this->assertNotSame($response, $original_response);
		$this->assertEquals($expected, (string) $response);
	}

	public function test_array_access_interface()
	{
		$dispatcher = new RequestDispatcher;
		$d1 = function() {};
		$k1 = 'd' . uniqid();

		$this->assertFalse(isset($dispatcher[$k1]));
		$dispatcher[$k1] = $d1;
		$this->assertTrue(isset($dispatcher[$k1]));
		$this->assertSame($d1, $dispatcher[$k1]);
		unset($dispatcher[$k1]);
		$this->assertFalse(isset($dispatcher[$k1]));

		try
		{
			$dispatcher[$k1];

			$this->fail('Expected DispatcherNotDefined');
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf(DispatcherNotDefined::class, $e);
		}
	}

	public function test_rescue_force_redirect()
	{
		$location = '/path/to/location/' . uniqid();

		$dispatcher = $this
			->getMockBuilder(RequestDispatcher::class)
			->disableOriginalConstructor()
			->onlyMethods([])
			->getMock();

		$force_redirect = new ForceRedirect($location);

		/* @var $dispatcher RequestDispatcher */

		$response = $dispatcher->rescue($force_redirect, Request::from('/'));

		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertSame($location, $response->location);
	}
}
