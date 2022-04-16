<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Responder\WithProvider;
use ICanBoogie\HTTP\ResponderProvider;
use ICanBoogie\HTTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

final class WithProviderTest extends TestCase
{
    private MockObject|ResponderProvider $responders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responders = $this->createMock(ResponderProvider::class);
    }

    /**
     * @throws Throwable
     */
    public function test_no_responder(): void
    {
        $request = Request::from();

        $this->responders
            ->method('responder_for_request')
            ->with($request)
            ->willReturn(null);

        $this->expectException(NotFound::class);

        $this->makeSTU()->respond($request);
    }

    /**
     * @throws Throwable
     */
    public function test_response(): void
    {
        $request = Request::from();
        $response = new Response();

        $responder = $this->createMock(Responder::class);
        $responder
            ->method('respond')
            ->with($request)
            ->willReturn($response);

        $this->responders
            ->method('responder_for_request')
            ->with($request)
            ->willReturn($responder);

        $this->assertSame(
            $response,
            $this->makeSTU()->respond($request)
        );
    }

    private function makeSTU(): Responder
    {
        return new WithProvider($this->responders);
    }
}
