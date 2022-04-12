<?php

namespace ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\PropertyNotWritable;

class DispatchEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestDispatcher
     */
    private $dispatcher;

    /**
     * @var Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = Request::from('/');
    }

    public function test_error_on_invalid_response_type()
    {
        $response = new \StdClass();

        $this->expectException(\Throwable::class);

        DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);
    }

    public function test_error_on_setting_invalid_response_type()
    {
        $response = null;

        /* @var $event DispatchEvent */

        $event = DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);

        $this->expectException(\Throwable::class);

        $event->response = new \StdClass();
    }

    public function test_should_accept_null()
    {
        $response = null;

        /* @var $event DispatchEvent */

        $event = DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);

        $this->assertNull($event->response);
        $event->response = null;
    }

    public function test_response()
    {
        $response = new Response();
        $response_replacement = new Response();

        /* @var $event DispatchEvent */

        $event = DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);

        $this->assertInstanceOf(Response::class, $event->response);
        $this->assertSame($response, $event->response);
        $event->response = $response_replacement;
        $this->assertSame($response_replacement, $event->response);
        $this->assertSame($response_replacement, $response);
    }

    public function test_should_read_request()
    {
        $response = null;

        /* @var $event DispatchEvent */

        $event = DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);

        $this->assertSame($this->request, $event->request);
    }

    public function test_should_throw_exception_on_write_request()
    {
        $response = null;

        /* @var $event DispatchEvent */

        $event = DispatchEvent::from([

            'target' => $this->dispatcher,
            'request' => $this->request,
            'response' => &$response

        ]);

        $this->expectException(PropertyNotWritable::class);
        $event->request = null;
    }
}
