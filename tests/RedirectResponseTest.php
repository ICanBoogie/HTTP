<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\RedirectResponse;
use ICanBoogie\HTTP\Status;
use ICanBoogie\HTTP\StatusCodeNotValid;
use PHPUnit\Framework\TestCase;

final class RedirectResponseTest extends TestCase
{
    public function test_construct(): void
    {
        $uri = "/go/to/there";
        $response = new RedirectResponse($uri);
        $this->assertTrue($response->status->is_redirect);
        $this->assertEquals(Status::FOUND, $response->status->code);
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

        new RedirectResponse("/go/to/there", Status::NOT_FOUND);
    }
}
