<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\Exception\RescueEvent;

class RescueEventTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_invalid_exception_type()
	{
		$exception = new \StdClass;
		$response = null;

		new RescueEvent($exception, Request::from('/'), $response);
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_setting_invalid_exception_type()
	{
		$exception = new \Exception;
		$response = null;
		$event = new RescueEvent($exception, Request::from('/'), $response);
		$event->exception = new \StdClass;
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_invalid_response_type()
	{
		$response = new \StdClass;

		new RescueEvent(new \Exception, Request::from('/'), $response);
	}

	/**
	 * @expectedException \PHPUnit_Framework_Error
	 */
	public function test_error_on_setting_invalid_response_type()
	{
		$response = null;
		$event = new RescueEvent(new \Exception, Request::from('/'), $response);
		$event->response = new \StdClass;
	}

	public function test_should_accept_null()
	{
		$response = null;
		$event = new RescueEvent(new \Exception, Request::from('/'), $response);
		$this->assertNull($event->response);
		$event->response = null;
	}

	public function test_should_accept_response()
	{
		$response = new Response;
		$event = new RescueEvent(new \Exception, Request::from('/'), $response);
		$this->assertInstanceOf('ICanBoogie\HTTP\Response', $event->response);
		$this->assertSame($response, $event->response);
		$event->response = $response;
	}
}
