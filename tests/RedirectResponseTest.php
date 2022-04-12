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
        $this->assertStringContainsString($uri, $body);
    }

    public function test_construct_with_invalid_code()
    {
        $this->expectException(StatusCodeNotValid::class);

        new RedirectResponse("/go/to/there", 987);
    }

    public function test_construct_with_not_redirect_code()
    {
        $this->expectException(StatusCodeNotValid::class);

        new RedirectResponse("/go/to/there", Status::NOT_FOUND);
    }
}
