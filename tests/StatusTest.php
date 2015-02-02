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
	 * @covers \ICanBoogie\HTTP\Status::set_code
	 */
	public function test_set_code_invalid()
	{
		$status = new Status;
		$status->code = 12345;
	}

	/**
	 * @covers \ICanBoogie\HTTP\Status::from
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
	 * @covers \ICanBoogie\HTTP\Status::from
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_invalid_status()
	{
		Status::from("Invalid status");
	}

	/**
	 * @covers \ICanBoogie\HTTP\Status::from
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_from_invalid_status_code()
	{
		Status::from("987 Invalid");
	}

	/**
	 * @covers \ICanBoogie\HTTP\Status::__construct
	 */
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
	 * @covers \ICanBoogie\HTTP\Status::__construct
	 * @expectedException \ICanBoogie\HTTP\StatusCodeNotValid
	 */
	public function test_constructor_with_invalid_code()
	{
		new Status(987);
	}

	/**
	 * @covers \ICanBoogie\HTTP\Status::get_is_cacheable
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
