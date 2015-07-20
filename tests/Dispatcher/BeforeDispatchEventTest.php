<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

class BeforeDispatchEventTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Dispatcher
	 */
	private $dispatcher;

	/**
	 * @var Request
	 */
	private $request;

	public function setUp()
	{
		$this->dispatcher = $this
			->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->getMock();

		$this->request = Request::from('/');
	}

	public function test_error_on_invalid_response_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException(\PHPUnit_Framework_Error::class);

		#

		$response = new \StdClass;

		new BeforeDispatchEvent($this->dispatcher, $this->request, $response);
	}

	public function test_error_on_setting_invalid_response_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException(\PHPUnit_Framework_Error::class);

		#

		$response = null;

		/* @var $event BeforeDispatchEvent */

		$event = BeforeDispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$event->response = new \StdClass;
	}

	public function test_should_accept_null()
	{
		$response = null;

		/* @var $event BeforeDispatchEvent */

		$event = BeforeDispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$this->assertNull($event->response);
		$event->response = null;
	}

	public function test_response()
	{
		$response = new Response;
		$response_replacement = new Response;

		/* @var $event BeforeDispatchEvent */
		$event = BeforeDispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$this->assertInstanceOf(Response::class, $event->response);
		$this->assertSame($response, $event->response);
		$event->response = $response_replacement;
		$this->assertSame($response_replacement, $event->response);
		$this->assertSame($response_replacement, $response);
	}

	public function test_should_read_request()
	{
		$response = null;

		/* @var $event BeforeDispatchEvent */

		$event = BeforeDispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$this->assertSame($this->request, $event->request);
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 */
	public function test_should_throw_exception_on_write_request()
	{
		$response = null;

		/* @var $event BeforeDispatchEvent */

		$event = BeforeDispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$event->request = null;
	}
}
