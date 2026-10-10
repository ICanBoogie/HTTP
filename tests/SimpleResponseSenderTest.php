<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\FinalResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\SimpleResponseSender;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SimpleResponseSenderTest extends TestCase
{
    public function test_sends_headers_and_body(): void
    {
        $sender = new RecordingResponseSender();
        $response = new Response('body', 201, [ 'X-Foo' => 'bar' ]);

        ob_start();
        $sender->send($response->finalize());
        $output = ob_get_clean();

        $this->assertSame('body', $output);
        $this->assertSame(1, $sender->sent_headers_count);
        $this->assertSame(201, $sender->final->status->code);
        $this->assertSame('bar', $sender->final->headers['X-Foo']);
    }

    public function test_closure_body_receives_the_final_response(): void
    {
        $sender = new RecordingResponseSender();
        $response = new Response(fn(FinalResponse $r) => print("status={$r->status->code}"), 202);

        ob_start();
        $sender->send($response->finalize());
        $output = ob_get_clean();

        $this->assertSame('status=202', $output);
    }

    public function test_body_is_not_sent_for_head(): void
    {
        $sender = new RecordingResponseSender();
        $request = Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_HEAD ]);

        ob_start();
        $sender->send((new Response('body'))->finalize($request));
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame(1, $sender->sent_headers_count);
    }

    #[DataProvider('provide_statuses_without_body')]
    public function test_body_is_not_sent_for_statuses_without_body(int $status): void
    {
        $sender = new RecordingResponseSender();

        ob_start();
        $sender->send((new Response('body', $status))->finalize());
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public static function provide_statuses_without_body(): array
    {
        return [ [ 100 ], [ 204 ], [ 304 ] ];
    }
}

final class RecordingResponseSender extends SimpleResponseSender
{
    public int $sent_headers_count = 0;
    public ?FinalResponse $final = null;

    protected function send_headers(FinalResponse $response): void
    {
        $this->sent_headers_count++;
        $this->final = $response;
    }
}
