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
	 * @expectedException ICanBoogie\PropertyNotWritable
	 *
	 * @param string $property Property name.
	 */
	public function test_write_readonly_properties($property)
	{
		self::$response->$property = null;
	}

	public function provide_test_write_readonly_properties()
	{
		$properties = 'is_valid is_informational is_successful is_redirect is_client_error'
		. ' is_server_error is_ok is_forbidden is_not_found is_empty is_validateable'
		. ' is_cacheable is_fresh';

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
	 * The `Content-Lenght` header field MUST be present, but it MUST NOT be added to the header
	 * instance.
	 */
	public function test_auto_content_length()
	{
		$r = new Response;
		$r->body = "Madonna";

		$this->assertEquals("HTTP/1.0 200 OK\r\nDate: {$r->date}\r\nContent-Length: 7\r\n\r\nMadonna", (string) $r);
		$this->assertNull($r->content_length);
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

	public function test_is_private()
	{
		$r = new Response;
		$this->assertEmpty($r->cache_control->cacheable);
		$this->assertFalse($r->is_private);
		$r->is_private = true;
		$this->assertTrue($r->is_private);
		$this->assertEquals('private', $r->cache_control->cacheable);
		$r->is_private = false;
		$this->assertFalse($r->is_private);
		$this->assertEquals('public', $r->cache_control->cacheable);
	}
}
