<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\Exception\RescueEvent;

class RescueEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_error_on_invalid_exception_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException('PHPUnit_Framework_Error');

		#

		$exception = new \stdClass;
		$response = null;

		new RescueEvent($exception, Request::from('/'), $response);
	}

	public function test_error_on_setting_invalid_exception_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException('PHPUnit_Framework_Error');

		#

		$exception = new \Exception;
		$response = null;
		$event = new RescueEvent($exception, Request::from('/'), $response);
		$event->exception = new \stdClass;
	}

	public function test_exception()
	{
		$exception = new \Exception;
		$exception_replacement = new \Exception;
		$response = null;
		$event = new RescueEvent($exception, Request::from('/'), $response);
		$this->assertSame($exception, $event->exception);
		$event->exception = $exception_replacement;
		$this->assertSame($exception_replacement, $event->exception);
		$this->assertSame($exception_replacement, $exception);
	}

	public function test_error_on_invalid_response_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException('PHPUnit_Framework_Error');

		#

		$response = new \StdClass;

		new RescueEvent(new \Exception, Request::from('/'), $response);
	}

	public function test_error_on_setting_invalid_response_type()
	{
		if (version_compare(PHP_VERSION, 7, '>='))
		{
			$this->markTestSkipped("Fatal error in PHP7");
		}

		$this->setExpectedException('PHPUnit_Framework_Error');

		#

		$response = null;
		$event = new RescueEvent(new \Exception, Request::from('/'), $response);
		$event->response = new \stdClass;
	}

	public function test_should_accept_null()
	{
		$exception = new \Exception;
		$response = null;
		$event = new RescueEvent($exception, Request::from('/'), $response);
		$this->assertNull($event->response);
		$event->response = null;
	}

	public function test_response()
	{
		$exception = new \Exception;
		$response = new Response;
		$response_replacement = new Response;
		$event = new RescueEvent($exception, Request::from('/'), $response);
		$this->assertInstanceOf(Response::class, $event->response);
		$this->assertSame($response, $event->response);
		$event->response = $response_replacement;
		$this->assertSame($response_replacement, $event->response);
		$this->assertSame($response_replacement, $response);
	}

	public function test_should_read_request()
	{
		$exception = new \Exception;
		$request = Request::from('/');
		$response = null;
		$event = new RescueEvent($exception, $request, $response);
		$this->assertSame($request, $event->request);
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 */
	public function test_should_throw_exception_on_write_request()
	{
		$exception = new \Exception;
		$request = Request::from('/');
		$response = null;
		$event = new RescueEvent($exception, $request, $response);
		$event->request = null;
	}
}
