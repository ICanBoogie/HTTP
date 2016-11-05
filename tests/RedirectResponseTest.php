<?php

namespace ICanBoogie\HTTP;

class RedirectResponseTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @covers \ICanBoogie\HTTP\RedirectResponse::__construct
	 */
	public function test_construct()
	{
		$uri = "/go/to/there";
		$response = new RedirectResponse($uri);
		$this->assertTrue($response->status->is_redirect);
		$this->assertEquals(Status::FOUND, $response->status->code);
		$this->assertEquals($uri, $response->location);

		$body = (string) $response;
		$this->assertContains($uri, $body);
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
		new RedirectResponse("/go/to/there", Status::NOT_FOUND);
	}
}
