<?php

namespace ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

class DispatchEventTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var RequestDispatcher
	 */
	private $dispatcher;

	/**
	 * @var Request
	 */
	private $request;

	public function setUp()
	{
		$this->dispatcher = $this
			->getMockBuilder(RequestDispatcher::class)
			->disableOriginalConstructor()
			->getMock();

		$this->request = Request::from('/');
	}

	public function test_error_on_invalid_response_type()
	{
		$this->expectException(version_compare(PHP_VERSION, 7, '>=')
			? \Throwable::class
			: \PHPUnit_Framework_Error::class);

		#

		$response = new \StdClass;

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);
	}

	public function test_error_on_setting_invalid_response_type()
	{
		$this->expectException(version_compare(PHP_VERSION, 7, '>=')
			? \Throwable::class
			: \PHPUnit_Framework_Error::class);

		#

		$response = null;

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$event->response = new \StdClass;
	}

	public function test_should_accept_null()
	{
		$response = null;

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

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

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

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

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

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

		/* @var $event DispatchEvent */

		$event = DispatchEvent::from([

			'target' => $this->dispatcher,
			'request' => $this->request,
			'response' => &$response

		]);

		$event->request = null;
	}
}
