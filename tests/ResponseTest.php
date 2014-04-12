<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Tests\HTTP\Response;

use ICanBoogie\HTTP\Response;
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
		$properties = 'is_valid|is_informational|is_successful|is_redirect|is_client_error'
		. '|is_server_error|is_ok|is_forbidden|is_not_found|is_empty|is_validateable'
		. '|is_cacheable|is_fresh';

		return array_map(function($v) { return (array) $v; }, explode('|', $properties));
	}

	public function test_date()
	{
		$r = new Response();
		$r->date = 'now';
		$this->assertInstanceOf('ICanBoogie\HTTP\DateHeader', $r->date);
		$this->assertEquals('UTC', $r->date->zone->name);
		$this->assertTrue(DateTime::now() == $r->date);
	}
}