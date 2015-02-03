<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\Exception\RescueEvent;

class RescueEventTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_should_throw_exception_on_invalid_response_type()
	{
		$response = new \StdClass;

		new RescueEvent(new \Exception, Request::from('/'), $response);
	}
}
