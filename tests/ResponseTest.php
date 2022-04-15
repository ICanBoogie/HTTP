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

use ICanBoogie\DateTime;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Headers\CacheControl;
use ICanBoogie\HTTP\Headers\Date;
use ICanBoogie\HTTP\Response;
use ICanBoogie\PropertyNotWritable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class ResponseTest extends TestCase
{
    private static $response;

    public static function setupBeforeClass(): void
    {
        self::$response = new Response();
    }

    public function test_clone()
    {
        $response = new Response();
        $clone = clone $response;

        $this->assertNotSame($clone->headers, $response->headers);
        $this->assertNotSame($clone->status, $response->status);
    }

    public function test_invalid_body_should_throw_exception()
    {
        $this->expectException(UnexpectedValueException::class);

        new Response(new \stdClass());
    }

    public function test_should_throw_exception_setting_empty_string_location()
    {
        $response = new Response();

        $this->expectException(InvalidArgumentException::class);

        $response->location = '';
    }

    public function test_should_remove_location_with_null()
    {
        $location = '/path/to/resource';
        $response = new Response();
        $response->location = $location;
        $this->assertEquals($location, $response->location);
        $response->location = null;
        $this->assertNull($response->location);
    }

    public function test_should_set_content_type()
    {
        $expected = 'application/json';
        $response = new Response();
        $response->content_type = $expected;
        $this->assertEquals($expected, $response->content_type);
        $response->content_type = null;
        $this->assertNull($response->content_type->value);
    }

    /**
     * @dataProvider provide_test_write_readonly_properties
     */
    public function test_write_readonly_properties(string $property)
    {
        $this->expectException(PropertyNotWritable::class);

        self::$response->$property = null;
    }

    public function provide_test_write_readonly_properties()
    {
        $properties = 'is_validateable is_cacheable is_fresh';

        return array_map(function ($name) {
            return (array) $name;
        }, explode(' ', $properties));
    }

    public function test_date()
    {
        $response = new Response();
        $this->assertInstanceOf(Headers\Date::class, $response->date);
        $this->assertTrue(DateTime::right_now()->as_iso8601 == $response->date->as_iso8601);

        $response->date = 'now';
        $this->assertInstanceOf(Headers\Date::class, $response->date);
        $this->assertEquals('UTC', $response->date->zone->name);
        $this->assertTrue(DateTime::right_now()->as_iso8601 == $response->date->as_iso8601);
    }

    public function test_age()
    {
        $response = new Response();
        $this->assertEquals(0, $response->age);

        $response->date = '-3 second';
        $this->assertEquals(3, $response->age);

        $response->date = null;
        $this->assertNull($response->age);

        $response->age = 123;
        $this->assertSame(123, $response->age);
    }

    public function test_etag()
    {
        $response = new Response();
        $this->assertNull($response->etag);

        $etag = uniqid();
        $response->etag = $etag;
        $this->assertSame($etag, $response->etag);

        $response->etag = null;
        $this->assertNull($response->etag);
    }

    public function test_cache_control()
    {
        $response = new Response();
        $this->assertInstanceOf(CacheControl::class, $response->headers->cache_control);
        $this->assertEmpty((string) $response->headers->cache_control);

        $value = "public, max-age=12345";
        $response->headers->cache_control = $value;
        $this->assertSame($value, (string) $response->headers->cache_control);

        $response->headers->cache_control = null;
        $this->assertEmpty((string) $response->headers->cache_control);
    }

    public function test_last_modified()
    {
        $response = new Response();
        $this->assertInstanceOf(Date::class, $response->last_modified);
        $this->assertEmpty((string) $response->last_modified);

        $value = DateTime::now();
        $response->last_modified = $value;
        $this->assertEquals($value, $response->last_modified);

        $response->last_modified = null;
        $this->assertEmpty((string) $response->last_modified);
    }

    public function test_expires()
    {
        $response = new Response();
        $this->assertInstanceOf(Date::class, $response->expires);
        $this->assertEmpty((string) $response->expires);

        $value = new DateTime('+1 days');
        $response->expires = $value;
        $this->assertEquals($value->as_iso8601, $response->expires->as_iso8601);
        $this->assertSame(86400, $response->headers->cache_control->max_age);

        $response->expires = null;
        $this->assertEmpty((string) $response->expires);
        $this->assertNull($response->headers->cache_control->max_age);
    }

    /**
     * The `Content-Length` header field MUST NOT be present, and MUST NOT be added to the header
     * instance.
     *
     * @dataProvider provide_test_no_content_length
     *
     * @param mixed $body
     */
    public function test_no_content_length($body)
    {
        $response = new Response($body);
        $response_string = (string) $response;

        $this->assertStringStartsWith("HTTP/1.1 200 OK\r\nDate: {$response->date}\r\n", $response_string);
        $this->assertStringNotContainsString("Content-Length", $response_string);
        $this->assertNull($response->content_length);
    }

    public function provide_test_no_content_length()
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

    public function test_auto_content_length_with_null()
    {
        $response = new Response();

        $this->assertEquals("HTTP/1.1 200 OK\r\nDate: {$response->date}\r\n\r\n", (string) $response);
        $this->assertNull($response->content_length);
    }

    public function test_preserve_content_length()
    {
        $response = new Response(null, Response::STATUS_OK, [

            'Content-Length' => 123

        ]);

        $this->assertEquals(123, $response->content_length);
        $this->assertEquals("HTTP/1.1 200 OK\r\nContent-Length: 123\r\nDate: {$response->date}\r\n\r\n", (string) $response);
    }

    public function test_is_validateable()
    {
        $response = new Response();
        $this->assertFalse($response->is_validateable);

        $response->headers['ETag'] = uniqid();
        $this->assertTrue($response->is_validateable);
        $response->headers['ETag'] = null;
        $this->assertFalse($response->is_validateable);

        $response->headers['Last-Modified'] = 'now';
        $this->assertTrue($response->is_validateable);
    }

    /**
     * @covers \Test\ICanBoogie\HTTP\Response::get_is_cacheable
     * @dataProvider provide_test_is_cacheable
     *
     * @param Response $response
     * @param bool $expected
     */
    public function test_is_cacheable($response, $expected)
    {
        $this->assertEquals($expected, $response->is_cacheable);
    }

    public function provide_test_is_cacheable()
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

    public function test_invoke()
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

    public function test_invoke_empty_body()
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

    public function test_to_string_with_exception()
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
