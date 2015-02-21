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
		new Response(null, 200, (object) []);
	}

	public function test_should_remove_location_with_null()
	{
		$location = '/path/to/resource';
		$r = new Response;
		$r->location = $location;
		$this->assertEquals($location, $r->location);
		$this->location = null;
		$this->assertNull($this->location);
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
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\Date', $r->date);
		$this->assertTrue(DateTime::now() == $r->date);

		$r->date = 'now';
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\Date', $r->date);
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
	 * The `Content-Length` header field MUST be present, but it MUST NOT be added to the header
	 * instance.
	 *
	 * @dataProvider provide_test_auto_content_length
	 */
	public function test_auto_content_length($body, $expected_value, $expected_length)
	{
		$r = new Response($body);

		$header_field = '';

		if ($expected_length)
		{
			$header_field = "Content-Length: $expected_length\r\n";
		}

		$this->assertStringStartsWith("HTTP/1.0 200 OK\r\nDate: {$r->date}\r\n{$header_field}\r\n", (string) $r);
		$this->assertSame($expected_value, $r->content_length);
	}

	public function provide_test_auto_content_length()
	{
		$now = DateTime::now();

		return [

			[ 123, 3, 3  ],
			[ 123.456, 7, 7 ],
			[ "Madonna", 7, 7 ],
			[ function() { return "Madonna"; }, null, null ],
			[ $now, null, strlen($now) ]

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
		$r = new Response(null, 200, [

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

			[ new Response('A', 200), false ],
			[ new Response('A', 200, [ 'Cache-Control' => "public" ]), false ],
			[ new Response('A', 200, [ 'Cache-Control' => "private" ]), false ],
			[ new Response('A', 405), false ],

			[ new Response('A', 200, [ 'Last-Modified' => 'yesterday' ]), true ],
			[ new Response('A', 200, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "public" ]), true ],
			[ new Response('A', 200, [ 'Last-Modified' => 'yesterday', 'Cache-Control' => "private" ]), false ],
			[ new Response('A', 405, [ 'Last-Modified' => 'yesterday' ]), false ]

		];
	}

	public function test_invoke()
	{
		$body = uniqid();

		$headers = $this
			->getMockBuilder('ICanBoogie\HTTP\Headers')
			->disableOriginalConstructor()
			->setMethods([ '__invoke' ])
			->getMock();

		$response = $this
			->getMockBuilder('ICanBoogie\HTTP\Response')
			->setConstructorArgs([ $body, 200, $headers ])
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
			->getMockBuilder('ICanBoogie\HTTP\Headers')
			->disableOriginalConstructor()
			->setMethods([ '__invoke' ])
			->getMock();

		$response = $this
			->getMockBuilder('ICanBoogie\HTTP\Response')
			->setConstructorArgs([ $body, 200, $headers ])
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
			->getMockBuilder('ICanBoogie\HTTP\Response')
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
