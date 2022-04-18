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

use Exception;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RecoverEvent;
use ICanBoogie\HTTP\Response;
use PHPUnit\Framework\TestCase;

final class RescueEventTest extends TestCase
{
    public function test_exception(): void
    {
        $exception = new Exception();
        $exception_replacement = new Exception();
        $response = null;
        $event = new RecoverEvent($exception, Request::from('/'), $response);
        $this->assertSame($exception, $event->exception);
        $event->exception = $exception_replacement;
        $this->assertSame($exception_replacement, $event->exception);
        $this->assertSame($exception_replacement, $exception);
    }

    public function test_should_accept_null(): void
    {
        $exception = new Exception();
        $response = null;
        $event = new RecoverEvent($exception, Request::from('/'), $response);
        $this->assertNull($event->response);
        $event->response = null;
    }

    public function test_response(): void
    {
        $exception = new Exception();
        $response = new Response();
        $response_replacement = new Response();
        $event = new RecoverEvent($exception, Request::from('/'), $response);
        $this->assertInstanceOf(Response::class, $event->response);
        $this->assertSame($response, $event->response);
        $event->response = $response_replacement;
        $this->assertSame($response_replacement, $event->response);
        $this->assertSame($response_replacement, $response);
    }

    public function test_should_read_request(): void
    {
        $exception = new Exception();
        $request = Request::from('/');
        $response = null;
        $event = new RecoverEvent($exception, $request, $response);
        $this->assertSame($request, $event->request);
    }
}
