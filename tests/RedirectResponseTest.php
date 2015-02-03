<?php

namespace ICanBoogie\HTTP;

class RedirectResponseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers \ICanBoogie\HTTP\RedirectResponse::__construct
	 */
	public function test_construct()
	{
		$p = "/go/to/there";
		$r = new RedirectResponse($p);
		$this->assertTrue($r->status->is_redirect);
		$this->assertEquals(302, $r->status->code);
		$this->assertEquals($p, $r->location);

		$body = (string) $r;
		$this->assertContains($p, $body);
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_construct_with_invalid_code()
	{
		new RedirectResponse("/go/to/there", 987);
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_construct_with_not_redirect_code()
	{
		new RedirectResponse("/go/to/there", 404);
	}
}
