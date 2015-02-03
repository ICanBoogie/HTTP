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

class StatusTest extends \PHPUnit_Framework_TestCase
{
	public function test_constructor()
	{
		$status = new Status(301, "Over the rainbow");

		$this->assertEquals(301, $status->code);
		$this->assertEquals("Over the rainbow", $status->message);
		$this->assertEquals("301 Over the rainbow", (string) $status);

		$status->message = null;
		$this->assertEquals("Moved Permanently", $status->message);
		$this->assertEquals("301 Moved Permanently", (string) $status);
	}

	/**
	 * @dataProvider provide_test_from
	 */
	public function test_from($source, $expected)
	{
		$status = Status::from($source);
		$this->assertEquals($expected, (string) $status);
	}

	public function provide_test_from()
	{
		return [

			[ 200 , "200 OK" ],
			[ [ 200, "Madonna" ], "200 Madonna" ],
			[ "200 Madonna", "200 Madonna" ]

		];
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_invalid_status()
	{
		Status::from("Invalid status");
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_from_invalid_status_code()
	{
		Status::from("987 Invalid");
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_constructor_with_invalid_code()
	{
		new Status(987);
	}

	/**
	 * @dataProvider provide_test_write_readonly_properties
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 *
	 * @param string $property Property name.
	 */
	public function test_write_readonly_properties($property)
	{
		$status = new Status;
		$status->$property = null;
	}

	public function provide_test_write_readonly_properties()
	{
		$properties = 'is_valid is_informational is_successful is_redirect is_client_error'
			. ' is_server_error is_ok is_forbidden is_not_found is_empty';

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_set_code_invalid()
	{
		$status = new Status;
		$status->code = 12345;
	}

	public function test_set_code()
	{
		$status = new Status;
		$status->code = 404;
		$this->assertEquals(404, $status->code);
	}

	public function test_is_valid()
	{
		$status = new Status(200);
		$this->assertTrue($status->is_valid);
	}

	public function test_is_informational()
	{
		$status = new Status(200);
		$this->assertFalse($status->is_informational);
		$status->code = 100;
		$this->assertTrue($status->is_informational);
		$status->code = 199;
		$this->assertTrue($status->is_informational);
	}

	public function test_is_successful()
	{
		$status = new Status;
		$status->code = 199;
		$this->assertFalse($status->is_successful);
		$status->code = 200;
		$this->assertTrue($status->is_successful);
		$status->code = 299;
		$this->assertTrue($status->is_successful);
		$status->code = 300;
		$this->assertFalse($status->is_successful);
	}

	public function test_is_redirect()
	{
		$status = new Status;
		$status->code = 299;
		$this->assertFalse($status->is_redirect);
		$status->code = 300;
		$this->assertTrue($status->is_redirect);
		$status->code = 399;
		$this->assertTrue($status->is_redirect);
		$status->code = 400;
		$this->assertFalse($status->is_redirect);
	}

	public function test_is_client_error()
	{
		$status = new Status;
		$status->code = 399;
		$this->assertFalse($status->is_client_error);
		$status->code = 400;
		$this->assertTrue($status->is_client_error);
		$status->code = 499;
		$this->assertTrue($status->is_client_error);
		$status->code = 500;
		$this->assertFalse($status->is_client_error);
	}

	public function test_is_server_error()
	{
		$status = new Status;
		$status->code = 499;
		$this->assertFalse($status->is_server_error);
		$status->code = 500;
		$this->assertTrue($status->is_server_error);
		$status->code = 599;
		$this->assertTrue($status->is_server_error);
	}

	public function test_is_ok()
	{
		$status = new Status;
		$this->assertTrue($status->is_ok);
		$status->code = 404;
		$this->assertFalse($status->is_ok);
	}

	public function test_is_forbidden()
	{
		$status = new Status;
		$this->assertFalse($status->is_forbidden);
		$status->code = 403;
		$this->assertTrue($status->is_forbidden);
	}

	public function test_is_not_found()
	{
		$status = new Status;
		$this->assertFalse($status->is_not_found);
		$status->code = 404;
		$this->assertTrue($status->is_not_found);
	}

	public function test_is_empty()
	{
		$status = new Status;
		$this->assertFalse($status->is_empty);
		$status->code = 201;
		$this->assertTrue($status->is_empty);
		$status->code = 204;
		$this->assertTrue($status->is_empty);
		$status->code = 304;
		$this->assertTrue($status->is_empty);
	}

	/**
	 * @dataProvider provide_test_is_cacheable
	 */
	public function test_is_cacheable($code, $expected)
	{
		$status = new Status($code);
		$this->assertEquals($expected, $status->is_cacheable);
	}

	public function provide_test_is_cacheable()
	{
		return [

			[ 200, true ],
			[ 201, false ],
			[ 202, false ],
			[ 203, true ],
			[ 300, true ],
			[ 301, true ],
			[ 302, true ],
			[ 303, false ],
			[ 404, true ],
			[ 405, false ],
			[ 410, true ]

		];
	}
}
