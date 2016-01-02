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

class MultipartTest extends \PHPUnit_Framework_TestCase
{
	public function test_empty()
	{
		$mp = new Multipart;

		$this->assertEmpty((string) $mp);
		$this->assertEquals(0, $mp->length);
	}

	public function test_to_string_and_length()
	{
		$mp = new Multipart([

			"PART1",
			"PART2",
			new Response("PART3", Response::STATUS_OK, [ 'Content-Type' => 'text/plain' ])

		]);

		$boundary = $mp->boundary;
		$expected = "{$boundary}\r\n\r\nPART1\r\n{$boundary}\r\n\r\nPART2\r\n{$boundary}\r\nContent-Type: text/plain\r\n\r\nPART3\r\n{$boundary}--";

		$this->assertEquals($expected, (string) $mp);
		$this->assertEquals(strlen($expected), $mp->length);
	}

	public function test_get_length_with_streaming_content()
	{
		$used_1 = false;
		$used_2 = false;

		$mp = new Multipart([

			new Response(function() use(&$used_1) {

				$used_1 = true;

				echo str_repeat('1', 10);

			}, Response::STATUS_OK, [ 'Content-Type' => 'text/plain', 'Content-Length' => 10 ]),

			new Response(function() use(&$used_2) {

				$used_2 = true;

				echo str_repeat('2', 20);

			}, Response::STATUS_OK, [ 'Content-Type' => 'text/plain', 'Content-Length' => 20 ]),

			"Some plain text"

		]);

		$boundary = $mp->boundary;
		$length = $mp->length;

		$this->assertFalse($used_1);
		$this->assertFalse($used_2);

		$this->assertEquals($length, strlen((string) $mp));
	}
}
