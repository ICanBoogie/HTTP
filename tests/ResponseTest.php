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

			[ new Response('A', 200), true ],
			[ new Response('A', 200, [ 'Cache-Control' => "public" ]), true ],
			[ new Response('A', 200, [ 'Cache-Control' => "private" ]), false ],
			[ new Response('A', 405), false ]

		];
	}
}
