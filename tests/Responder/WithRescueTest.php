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

use Exception;
use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RecoverEvent;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Responder\WithRecovery;
use ICanBoogie\HTTP\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

final class WithRescueTest extends TestCase
{
    use ProphecyTrait;

    private Request $request;
    private Response $response;
    private Throwable $exception;
    private ObjectProphecy|Responder $responder;
    private EventCollection $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = Request::from();
        $this->response = new Response();
        $this->exception = new NotFound();
        $this->responder = $this->prophesize(Responder::class);
        $this->events = new EventCollection();

        EventCollectionProvider::define(fn() => $this->events);
    }

    /**
     * @throws Throwable
     */
    public function test_nothing_to_rescue(): void
    {
        $this->responder->respond($this->request)
            ->willReturn($this->response);

        $this->assertSame(
            $this->response,
            $this->makeSTU()->respond($this->request)
        );
    }

    /**
     * @throws Throwable
     */
    public function test_rescue_failed(): void
    {
        $this->responder->respond($this->request)
            ->willThrow($this->exception);

        try {
            $this->makeSTU()->respond($this->request);
            $this->fail("Expected failure");
        } catch (Throwable $e) {
            $this->assertSame($this->exception, $e);
        }
    }

    /**
     * @throws Throwable
     */
    public function test_rescue_failed_but_got_new_exception(): void
    {
        $new_exception = new Exception();

        $this->responder->respond($this->request)
            ->willThrow($this->exception);

        $this->events->attach(function (RecoverEvent $event, NotFound $target) use ($new_exception) {
            $event->exception = $new_exception;
        });

        try {
            $this->makeSTU()->respond($this->request);
            $this->fail("Expected failure");
        } catch (Throwable $e) {
            $this->assertSame($new_exception, $e);
        }
    }

    /**
     * @throws Throwable
     */
    public function test_rescue(): void
    {
        $new_response = new Response();

        $this->responder->respond($this->request)
            ->willThrow($this->exception);

        $this->events->attach(function (RecoverEvent $event, NotFound $target) use ($new_response) {
            $event->response = $new_response;
        });

        $this->assertSame(
            $new_response,
            $this->makeSTU()->respond($this->request)
        );
    }

    private function makeSTU(): Responder
    {
        return new WithRecovery($this->responder->reveal());
    }
}
