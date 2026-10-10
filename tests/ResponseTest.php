<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\DateTime;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Headers\Date;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;
use ICanBoogie\HTTP\Response;
use ICanBoogie\PropertyNotWritable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_clone(): void
    {
        $response = new Response();
        $clone = clone $response;

        $this->assertNotSame($clone->headers, $response->headers);
        $this->assertNotSame($clone->status, $response->status);
    }

    public function test_should_set_content_type(): void
    {
        $expected = 'application/json';
        $response = new Response();
        $response->headers->content_type = $expected;
        $this->assertEquals($expected, $response->headers->content_type);
        $response->headers->content_type = null;
        $this->assertNull($response->headers->content_type->value);
    }

    public function test_age(): void
    {
        $response = new Response();
        $this->assertEquals(0, $response->age);

        $response->headers->date = '-3 second';
        $this->assertEquals(3, $response->age);

        $response->headers->date = null;
        $this->assertNull($response->age);

        $response->age = 123;
        $this->assertSame(123, $response->age);
    }

    public function test_expires(): void
    {
        $response = new Response();
        $this->assertInstanceOf(Date::class, $response->expires);
        $this->assertEmpty((string) $response->expires);

        $value = new DateTime('+1 days');
        $response->expires = $value;
        $this->assertEquals($value, $response->expires->delegate);
        $this->assertSame(86400, $response->headers->cache_control->max_age);

        $response->expires = null;
        $this->assertEmpty((string) $response->expires);
        $this->assertNull($response->headers->cache_control->max_age);
    }

    /**
     * The `Content-Length` header field MUST NOT be present, and MUST NOT be added to the header
     * instance.
     */
    #[DataProvider('provide_test_no_content_length')]
    public function test_no_content_length(mixed $body): void
    {
        $response = new Response($body);
        $response_string = (string) $response;

        $this->assertStringStartsWith("HTTP/1.1 200 OK\r\nDate: {$response->headers->date}\r\n", $response_string);
        $this->assertStringNotContainsString("Content-Length", $response_string);
    }

    public static function provide_test_no_content_length(): array
    {
        $now = DateTime::now();

        return [

            [ 123  ],
            [ 123.456 ],
            [ "Madonna" ],
            [ function () {
                return "Madonna";
            } ],
            [ $now ]

        ];
    }

    public function test_auto_content_length_with_null(): void
    {
        $response = new Response();

        $this->assertEquals("HTTP/1.1 200 OK\r\nDate: {$response->headers->date}\r\n\r\n", (string) $response);
    }

    public function test_preserve_content_length(): void
    {
        $response = new Response(null, Response::STATUS_OK, [

            'Content-Length' => 123

        ]);

        $this->assertEquals("HTTP/1.1 200 OK\r\nContent-Length: 123\r\nDate: {$response->headers->date}\r\n\r\n", (string) $response);
    }

    public function test_is_validateable(): void
    {
        $response = new Response();
        $this->assertFalse($response->is_validateable);

        $response->headers->etag = uniqid();
        $this->assertTrue($response->is_validateable);
        $response->headers->etag = null;
        $this->assertFalse($response->is_validateable);

        $response->headers->last_modified = 'now';
        $this->assertTrue($response->is_validateable);
    }

    #[DataProvider('provide_test_is_cacheable')]
    public function test_is_cacheable(Response $response, bool $expected): void
    {
        $this->assertEquals($expected, $response->is_cacheable);
    }

    public static function provide_test_is_cacheable(): array
    {
        return [

            [ new Response('A', Response::STATUS_OK), false ],
            [ new Response('A', Response::STATUS_OK, [ 'Cache-Control' => "public" ]), false ],
            [ new Response('A', Response::STATUS_OK, [ 'Cache-Control' => "private" ]), false ],
            [ new Response('A', 405), false ],

            [ new Response('A', Response::STATUS_OK, [ 'Last-Modified' => 'yesterday' ]), true ],
            [ new Response('A', Response::STATUS_OK, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "public" ]), true ],
            [ new Response('A', Response::STATUS_OK, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "private" ]), false ],
            [ new Response('A', 405, [ 'Last-Modified' => 'yesterday' ]), false ]

        ];
    }

    public function test_to_string_with_exception(): void
    {
        $exception = new \Exception('Message' . uniqid());
        $response = new Response(fn() => throw $exception);

        try {
            (string) $response;

            $this->fail("Expected exception was not thrown");
        } catch (\Exception $actual) {
            $this->assertSame($exception, $actual);
        }
    }

    #[DataProvider('provide_statuses_without_body')]
    public function test_body_is_discarded_for_statuses_without_body(int $status): void
    {
        $response = new Response('body', $status);
        $this->assertStringEndsWith("\r\n\r\n", (string) $response);

        $response = new Response(fn() => print('body'), $status);
        $this->assertStringEndsWith("\r\n\r\n", (string) $response);
    }

    /**
     * @return array<string, array{ int }>
     */
    public static function provide_statuses_without_body(): array
    {
        return [ '100' => [ 100 ], '204' => [ 204 ], '304' => [ 304 ] ];
    }

    public function test_body_is_kept_for_other_statuses(): void
    {
        $this->assertStringEndsWith("\r\n\r\nbody", (string) new Response('body', 201));
    }

    public function test_ttl_round_trips(): void
    {
        $response = new Response();
        $response->headers->date = '-10 second';
        $this->assertNull($response->ttl);

        $response->ttl = 60;
        $this->assertSame(70, $response->headers->cache_control->s_maxage);
        $this->assertSame(60, $response->ttl);

        $response->ttl = 0;
        $this->assertSame(0, $response->ttl);
        $this->assertFalse($response->is_fresh);

        $response->ttl = null;
        $this->assertNull($response->ttl);
    }

    public function test_ttl_falls_back_to_max_age(): void
    {
        $response = new Response();
        $response->headers->date = '-10 second';
        $response->headers->cache_control->max_age = 100;

        $this->assertSame(90, $response->ttl);
        $this->assertTrue($response->is_fresh);

        $response->headers->cache_control->s_maxage = 40;
        $this->assertSame(30, $response->ttl);
    }

    public function test_version(): void
    {
        $response = new Response();
        $response->version = '1.0';
        $this->assertSame('1.0', $response->version);

        $this->expectException(\InvalidArgumentException::class);
        $response->version = '2';
    }

    public function test_finalize_does_not_modify_the_response(): void
    {
        $response = new Response(new class implements \Stringable {
            public function __toString(): string
            {
                return 'body';
            }
        }, 204, [ 'X-Foo' => 'bar' ]);

        $final = $response->finalize();

        $this->assertNull($final->body);
        $this->assertNotSame($response->headers, $final->headers);
        $this->assertNotSame($response->status, $final->status);
        $this->assertSame('bar', $final->headers['X-Foo']);
        $this->assertInstanceOf(\Stringable::class, $response->body);
    }

    public function test_finalize_converts_stringable_body(): void
    {
        $body = new class implements \Stringable {
            public function __toString(): string
            {
                return 'body';
            }
        };

        $this->assertSame('body', (new Response($body))->finalize()->body);
    }

    public function test_finalize_omits_the_body_for_head(): void
    {
        $head = Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_HEAD ]);
        $response = new Response('body', 200, [ 'Content-Length' => 4 ]);

        $this->assertNull($response->finalize($head)->body);
        $this->assertSame(4, $response->finalize($head)->headers->content_length);
        $this->assertSame('body', $response->finalize()->body);
    }
}
