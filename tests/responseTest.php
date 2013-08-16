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
	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsValid()
	{
		$r = new Response();
		$r->is_valid = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsInformational()
	{
		$r = new Response();
		$r->is_informational = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsSuccessful()
	{
		$r = new Response();
		$r->is_successful = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsRedirect()
	{
		$r = new Response();
		$r->is_redirect = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsClientError()
	{
		$r = new Response();
		$r->is_client_error = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsServerError()
	{
		$r = new Response();
		$r->is_server_error = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsOk()
	{
		$r = new Response();
		$r->is_ok = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsForbidden()
	{
		$r = new Response();
		$r->is_forbidden = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsNotFound()
	{
		$r = new Response();
		$r->is_not_found = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsEmpty()
	{
		$r = new Response();
		$r->is_empty = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsValidateable()
	{
		$r = new Response();
		$r->is_validateable = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsCacheable()
	{
		$r = new Response();
		$r->is_cacheable = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function testWriteIsFresh()
	{
		$r = new Response();
		$r->is_fresh = true;
	}

	public function testDate()
	{
		$r = new Response();
		$r->date = 'now';
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\DateTime', $r->date);
		$this->assertEquals('UTC', $r->date->zone->name);
		$this->assertTrue(DateTime::now() == $r->date);
	}
}