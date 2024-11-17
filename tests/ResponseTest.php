<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\DateTime;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Headers\Date;
use ICanBoogie\HTTP\Response;
use ICanBoogie\PropertyNotWritable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private static Response $response;

    public static function setupBeforeClass(): void
    {
        self::$response = new Response();
    }

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

    #[DataProvider('provide_test_write_readonly_properties')]
    public function test_write_readonly_properties(string $property): void
    {
        $this->expectException(PropertyNotWritable::class);

        self::$response->$property = null;
    }

    public static function provide_test_write_readonly_properties(): array
    {
        $properties = 'is_validateable is_cacheable is_fresh';

        return array_map(function ($name) {
            return (array) $name;
        }, explode(' ', $properties));
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
        $this->assertEquals($value, $response->expires);
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

    public function test_invoke(): void
    {
        $body = uniqid();

        $headers = $this
            ->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ '__invoke' ])
            ->getMock();

        $response = $this
            ->getMockBuilder(Response::class)
            ->setConstructorArgs([ $body, Response::STATUS_OK, $headers ])
            ->onlyMethods([ 'finalize', 'send_headers', 'send_body' ])
            ->getMock();
        $response
            ->expects($this->once())
            ->method('finalize')
            ->with($this->equalTo($headers), $body);
        $response
            ->expects($this->once())
            ->method('send_headers')
            ->with($this->equalTo($headers))
            ->willReturn(true);
        $response
            ->expects($this->once())
            ->method('send_body')
            ->with($body);

        /* @var $response Response */

        $response();
    }

    public function test_invoke_empty_body(): void
    {
        $body = null;

        $headers = $this
            ->getMockBuilder(Headers::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ '__invoke' ])
            ->getMock();

        $response = $this
            ->getMockBuilder(Response::class)
            ->setConstructorArgs([ $body, Response::STATUS_OK, $headers ])
            ->onlyMethods([ 'finalize', 'send_headers', 'send_body' ])
            ->getMock();
        $response
            ->expects($this->once())
            ->method('finalize')
            ->with($this->equalTo($headers), $body);
        $response
            ->expects($this->once())
            ->method('send_headers')
            ->with($this->equalTo($headers))
            ->willReturn(true);
        $response
            ->expects($this->never())
            ->method('send_body')
            ->with($body);

        /* @var $response Response */

        $response();
    }

    public function test_to_string_with_exception(): void
    {
        $body = uniqid();

        $exception = new \Exception('Message' . uniqid());

        $response = $this
            ->getMockBuilder(Response::class)
            ->setConstructorArgs([ $body ])
            ->onlyMethods([ 'finalize', 'send_headers', 'send_body' ])
            ->getMock();
        $response
            ->expects($this->once())
            ->method('finalize')
            ->willThrowException($exception);

        $this->assertEquals($exception->getMessage(), (string) $response);
    }
}
