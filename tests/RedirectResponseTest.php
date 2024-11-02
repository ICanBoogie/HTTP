<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\ResponseStatus;
use ICanBoogie\HTTP\StatusCodeNotValid;
use PHPUnit\Framework\TestCase;

final class RedirectResponseTest extends TestCase
{
    public function test_construct(): void
    {
        $uri = "/go/to/there";
        $response = new RedirectResponse($uri);
        $this->assertTrue($response->status->is_redirect);
        $this->assertEquals(ResponseStatus::STATUS_FOUND, $response->status->code);
        $this->assertEquals($uri, $response->headers->location);

        $body = (string) $response;
        $this->assertStringContainsString($uri, $body);
    }

    public function test_construct_with_invalid_code(): void
    {
        $this->expectException(StatusCodeNotValid::class);

        new RedirectResponse("/go/to/there", 987);
    }

    public function test_construct_with_not_redirect_code(): void
    {
        $this->expectException(StatusCodeNotValid::class);

        new RedirectResponse("/go/to/there", ResponseStatus::STATUS_NOT_FOUND);
    }
}
