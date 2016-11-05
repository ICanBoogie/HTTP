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

class RequestRangeTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider provide_invalid_range
	 *
	 * @param $headers
	 * @param $total
	 * @param $etag
	 */
	public function test_should_return_null_when_undefined_or_modified($headers, $total, $etag)
	{
		$this->assertNull(RequestRange::from(new Headers($headers), $total, $etag));
	}

	public function provide_invalid_range()
	{
		$etag = uniqid();

		return [

			[ [ ], 10000, uniqid() ],
			[ [ 'Range' => 'bytes=1-10', 'If-Range' => uniqid() ], 10000, uniqid() ],
			[ [ 'Range' => 'bytes', 'If-Range' => $etag ], 10000, $etag ],
			[ [ 'Range' => '0-499', 'If-Range' => $etag ], 10000, $etag ],
			[ [ 'Range' => 'bytes=-', 'If-Range' => $etag ], 10000, $etag ]

		];
	}

	/**
	 * @dataProvider provide_unsatisfiable
	 *
	 * @param string $range
	 */
	public function test_should_be_unsatisfiable($range)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

		$this->assertFalse(RequestRange::from($headers, 10000, $etag)->is_satisfiable);
	}

	public function provide_unsatisfiable()
	{
		return [

			[ 'bytes=-11000' ],
			[ 'bytes=11000-' ],
			[ 'bytes=999-500' ],

		];
	}

	/**
	 * @dataProvider provide_valid_range
	 *
	 * @param string $range
	 * @param string $expected
	 */
	public function test_should_return_range($range, $expected)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);
		$range = RequestRange::from($headers, 10000, $etag);

		$this->assertTrue($range->is_satisfiable);
		$this->assertEquals($expected, (string) $range);
	}

	public function provide_valid_range()
	{
		return [

			[ 'bytes=0-499', 'bytes 0-499/10000' ],
			[ 'bytes=500-999', 'bytes 500-999/10000' ],
			[ 'bytes=-500', 'bytes 9500-9999/10000' ],
			[ 'bytes=9500-', 'bytes 9500-9999/10000' ]

		];
	}

	/**
	 * @dataProvider provide_test_is_total
	 *
	 * @param $range
	 * @param $expected
	 */
	public function test_is_total($range, $expected)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

		$this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->is_total);
	}

	public function provide_test_is_total()
	{
		return [

			[ 'bytes=0-499', false ],
			[ 'bytes=500-999', false ],
			[ 'bytes=-500', false ],
			[ 'bytes=9500-', false ],
			[ 'bytes=0-9999', true ]

		];
	}

	/**
	 * @dataProvider provide_test_length
	 *
	 * @param $range
	 * @param $expected
	 */
	public function test_length($range, $expected)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

		$this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->length);
	}

	public function provide_test_length()
	{
		return [

			[ 'bytes=0-499', 500 ],
			[ 'bytes=500-999', 500 ],
			[ 'bytes=-500', 500 ],
			[ 'bytes=9500-', 500 ],
			[ 'bytes=0-9999', 10000 ]

		];
	}

	/**
	 * @dataProvider provide_test_max_length
	 *
	 * @param $range
	 * @param $expected
	 */
	public function test_max_length($range, $expected)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

		$this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->max_length);
	}

	public function provide_test_max_length()
	{
		return [

			[ 'bytes=0-499', 500 ],
			[ 'bytes=500-999', 500 ],
			[ 'bytes=-500', 500 ],
			[ 'bytes=9500-', 500 ],
			[ 'bytes=0-9999', 10000 ],
			[ 'bytes=0-12000', -1 ]

		];
	}

	/**
	 * @dataProvider provide_test_offset
	 *
	 * @param $range
	 * @param $expected
	 */
	public function test_offset($range, $expected)
	{
		$etag = uniqid();
		$headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

		$this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->offset);
	}

	public function provide_test_offset()
	{
		return [

			[ 'bytes=0-499', 0 ],
			[ 'bytes=500-999', 500 ],
			[ 'bytes=-500', 9500 ],
			[ 'bytes=9500-', 9500 ],
			[ 'bytes=0-9999', 0 ]

		];
	}
}
