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

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Responder\WithEvent;
use ICanBoogie\HTTP\Responder\WithEvent\BeforeRespondEvent;
use ICanBoogie\HTTP\Responder\WithEvent\RespondEvent;
use ICanBoogie\HTTP\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WithEventTest extends TestCase
{
    private Request $request;
    private Response $response;
    private MockObject|Responder $responder;
    private EventCollection $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = Request::from();
        $this->response = new Response();
        $this->responder = $this->createMock(Responder::class);
        $this->events = new EventCollection();

        EventCollectionProvider::define(fn() => $this->events);
    }

    public function test_no_changes(): void
    {
        $this->responder
            ->method('respond')
            ->with($this->request)
            ->willReturn($this->response);

        $actual = $this->makeSTU()->respond($this->request);

        $this->assertSame($this->response, $actual);
    }

    public function test_response_provided_before(): void
    {
        $this->responder
            ->expects($this->never())
            ->method('respond')
            ->with(Assert::anything());

        $response = new Response();

        $this->events->attach(function (BeforeRespondEvent $event) use ($response) {
            $event->response = $response;
        });

        $actual = $this->makeSTU()->respond($this->request);

        $this->assertSame($response, $actual);
    }

    public function test_response_provided_after(): void
    {
        $this->responder
            ->method('respond')
            ->with($this->request)
            ->willReturn($this->response);

        $response = new Response();

        $this->events->attach(function (RespondEvent $event) use ($response) {
            $event->response = $response;
        });

        $actual = $this->makeSTU()->respond($this->request);

        $this->assertSame($response, $actual);
    }

    private function makeSTU(): Responder
    {
        return new WithEvent($this->responder);
    }
}
