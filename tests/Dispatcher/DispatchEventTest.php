<?php

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

class DispatchEventTest extends \PHPUnit_Framework_TestCase
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
			->getMockBuilder('ICanBoogie\HTTP\Dispatcher')
			->disableOriginalConstructor()
			->getMock();

		$this->request = Request::from('/');
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_invalid_response_type()
	{
		$response = new \StdClass;

		new DispatchEvent($this->dispatcher, $this->request, $response);
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_setting_invalid_response_type()
	{
		$response = null;
		$event = new DispatchEvent($this->dispatcher, $this->request, $response);
		$event->response = new \StdClass;
	}

	public function test_should_accept_null()
	{
		$response = null;
		$event = new DispatchEvent($this->dispatcher, $this->request, $response);
		$this->assertNull($event->response);
		$event->response = null;
	}

	public function test_response()
	{
		$response = new Response;
		$response_replacement = new Response;
		$event = new DispatchEvent($this->dispatcher, $this->request, $response);
		$this->assertInstanceOf('ICanBoogie\HTTP\Response', $event->response);
		$this->assertSame($response, $event->response);
		$event->response = $response_replacement;
		$this->assertSame($response_replacement, $event->response);
		$this->assertSame($response_replacement, $response);
	}

	public function test_should_read_request()
	{
		$response = null;
		$event = new DispatchEvent($this->dispatcher, $this->request, $response);
		$this->assertSame($this->request, $event->request);
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 */
	public function test_should_throw_exception_on_write_request()
	{
		$response = null;
		$event = new DispatchEvent($this->dispatcher, $this->request, $response);
		$event->request = null;
	}
}
