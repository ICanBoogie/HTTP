<?php

namespace ICanBoogie\HTTP;

class RedirectResponseTest extends \PHPUnit_Framework_TestCase
{
	public function test_construct()
	{
		$r = new RedirectResponse("/go/to/there");
		$this->assertTrue($r->is_redirect);
		$this->assertEquals(302, $r->status);
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
