<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use ICanBoogie\DateTime;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
	static private $response;

	static public function setupBeforeClass()
	{
		self::$response = new Response;
	}

	public function test_clone()
	{
		$r1 = new Response;
		$r2 = clone $r1;

		$this->assertNotSame($r2->headers, $r1->headers);
		$this->assertNotSame($r2->status, $r1->status);
	}

	/**
	 * @expectedException \UnexpectedValueException
	 */
	public function test_invalid_body_should_throw_exception()
	{
		new Response(new \Stdclass);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_should_throw_exception_setting_empty_string_location()
	{
		$r = new Response;
		$r->location = '';
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_invalid_headers_should_throw_exception()
	{
		new Response(null, Status::OK, (object) []);
	}

	public function test_should_remove_location_with_null()
	{
		$location = '/path/to/resource';
		$r = new Response;
		$r->location = $location;
		$this->assertEquals($location, $r->location);
		$r->location = null;
		$this->assertNull($r->location);
	}

	public function test_should_set_content_type()
	{
		$expected = 'application/json';
		$r = new Response;
		$r->content_type = $expected;
		$this->assertEquals($expected, $r->content_type);
		$r->content_type = null;
		$this->assertNull($r->content_type->value);
	}

	/**
	 * @dataProvider provide_test_write_readonly_properties
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 *
	 * @param string $property Property name.
	 */
	public function test_write_readonly_properties($property)
	{
		self::$response->$property = null;
	}

	public function provide_test_write_readonly_properties()
	{
		$properties = 'is_validateable is_cacheable is_fresh';

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	public function test_date()
	{
		$r = new Response;
		$this->assertInstanceOf(Headers\Date::class, $r->date);
		$this->assertTrue(DateTime::now() == $r->date);

		$r->date = 'now';
		$this->assertInstanceOf(Headers\Date::class, $r->date);
		$this->assertEquals('UTC', $r->date->zone->name);
		$this->assertTrue(DateTime::now() == $r->date);
	}

	public function test_age()
	{
		$r = new Response;
		$this->assertEquals(0, $r->age);

		$r->date = '-3 second';
		$this->assertEquals(3, $r->age);

		$r->age = 123;
		$this->assertSame(123, $r->age);
	}

	/**
	 * The `Content-Length` header field MUST NOT be present, and MUST NOT be added to the header
	 * instance.
	 *
	 * @dataProvider provide_test_no_content_length
	 */
	public function test_no_content_length($body)
	{
		$r = new Response($body);
		$s = (string) $r;

		$this->assertStringStartsWith("HTTP/1.0 200 OK\r\nDate: {$r->date}\r\n", $s);
		$this->assertNotContains("Content-Length", $s);
		$this->assertNull($r->content_length);
	}

	public function provide_test_no_content_length()
	{
		$now = DateTime::now();

		return [

			[ 123  ],
			[ 123.456 ],
			[ "Madonna" ],
			[ function() { return "Madonna"; } ],
			[ $now ]

		];
	}

	public function test_auto_content_length_with_null()
	{
		$r = new Response;

		$this->assertEquals("HTTP/1.0 200 OK\r\nDate: {$r->date}\r\n\r\n", (string) $r);
		$this->assertNull($r->content_length);
	}

	public function test_preserve_content_length()
	{
		$r = new Response(null, Status::OK, [

			'Content-Length' => 123

		]);

		$this->assertEquals(123, $r->content_length);
		$this->assertEquals("HTTP/1.0 200 OK\r\nContent-Length: 123\r\nDate: {$r->date}\r\n\r\n", (string) $r);
	}

	public function test_is_validateable()
	{
		$response = new Response;
		$this->assertFalse($response->is_validateable);

		$response->headers['ETag'] = uniqid();
		$this->assertTrue($response->is_validateable);
		$response->headers['ETag'] = null;
		$this->assertFalse($response->is_validateable);

		$response->headers['Last-Modified'] = 'now';
		$this->assertTrue($response->is_validateable);
	}

	/**
	 * @covers \ICanBoogie\HTTP\Response::get_is_cacheable
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

			[ new Response('A', Status::OK), false ],
			[ new Response('A', Status::OK, [ 'Cache-Control' => "public" ]), false ],
			[ new Response('A', Status::OK, [ 'Cache-Control' => "private" ]), false ],
			[ new Response('A', 405), false ],

			[ new Response('A', Status::OK, [ 'Last-Modified' => 'yesterday' ]), true ],
			[ new Response('A', Status::OK, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "public" ]), true ],
			[ new Response('A', Status::OK, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "private" ]), false ],
			[ new Response('A', 405, [ 'Last-Modified' => 'yesterday' ]), false ]

		];
	}

	public function test_invoke()
	{
		$body = uniqid();

		$headers = $this
			->getMockBuilder(Headers::class)
			->disableOriginalConstructor()
			->setMethods([ '__invoke' ])
			->getMock();

		$response = $this
			->getMockBuilder(Response::class)
			->setConstructorArgs([ $body, Status::OK, $headers ])
			->setMethods([ 'finalize', 'send_headers', 'send_body' ])
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
			->setMethods([ '__invoke' ])
			->getMock();

		$response = $this
			->getMockBuilder(Response::class)
			->setConstructorArgs([ $body, Status::OK, $headers ])
			->setMethods([ 'finalize', 'send_headers', 'send_body' ])
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
			->setMethods([ 'finalize', 'send_headers', 'send_body' ])
			->getMock();
		$response
			->expects($this->once())
			->method('finalize')
			->willThrowException($exception);

		$this->assertEquals($exception->getMessage(), (string) $response);
	}
}
