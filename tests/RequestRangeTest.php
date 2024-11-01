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

use PHPUnit\Framework\Attributes\DataProvider;

class RequestRangeTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('provide_invalid_range')]
    public function test_should_return_null_when_undefined_or_modified($headers, $total, $etag)
    {
        $this->assertNull(RequestRange::from(new Headers($headers), $total, $etag));
    }

    public static function provide_invalid_range()
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

    #[DataProvider('provide_unsatisfiable')]
    public function test_should_be_unsatisfiable(string $range)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertFalse(RequestRange::from($headers, 10000, $etag)->is_satisfiable);
    }

    public static function provide_unsatisfiable()
    {
        return [

            [ 'bytes=-11000' ],
            [ 'bytes=11000-' ],
            [ 'bytes=999-500' ],

        ];
    }

    #[DataProvider('provide_valid_range')]
    public function test_should_return_range(string $range, string $expected)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);
        $range = RequestRange::from($headers, 10000, $etag);

        $this->assertTrue($range->is_satisfiable);
        $this->assertEquals($expected, (string) $range);
    }

    public static function provide_valid_range()
    {
        return [

            [ 'bytes=0-499', 'bytes 0-499/10000' ],
            [ 'bytes=500-999', 'bytes 500-999/10000' ],
            [ 'bytes=-500', 'bytes 9500-9999/10000' ],
            [ 'bytes=9500-', 'bytes 9500-9999/10000' ]

        ];
    }

    #[DataProvider('provide_test_is_total')]
    public function test_is_total($range, $expected)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->is_total);
    }

    public static function provide_test_is_total()
    {
        return [

            [ 'bytes=0-499', false ],
            [ 'bytes=500-999', false ],
            [ 'bytes=-500', false ],
            [ 'bytes=9500-', false ],
            [ 'bytes=0-9999', true ]

        ];
    }

    #[DataProvider('provide_test_length')]
    public function test_length($range, $expected)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->length);
    }

    public static function provide_test_length()
    {
        return [

            [ 'bytes=0-499', 500 ],
            [ 'bytes=500-999', 500 ],
            [ 'bytes=-500', 500 ],
            [ 'bytes=9500-', 500 ],
            [ 'bytes=0-9999', 10000 ]

        ];
    }

    #[DataProvider('provide_test_max_length')]
    public function test_max_length($range, $expected)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->max_length);
    }

    public static function provide_test_max_length()
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

    #[DataProvider('provide_test_offset')]
    public function test_offset($range, $expected)
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->offset);
    }

    public static function provide_test_offset()
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
