<?php

namespace ICanBoogie\HTTP;

class RedirectResponseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers \ICanBoogie\HTTP\RedirectResponse::__construct
	 */
	public function test_construct()
	{
		$r = new RedirectResponse("/go/to/there");
		$this->assertTrue($r->status->is_redirect);
		$this->assertEquals(302, $r->status->code);
		$this->assertEquals("/go/to/there", $r->location);
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_construct_with_invalid_code()
	{
		new RedirectResponse("/go/to/there", 987);
	}
}
