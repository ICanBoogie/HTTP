<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\RequestRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequestRangeTest extends TestCase
{
    #[DataProvider('provide_invalid_range')]
    public function test_should_return_null_when_undefined_or_modified($headers, $total, $etag): void
    {
        $this->assertNull(RequestRange::from(new Headers($headers), $total, $etag));
    }

    public static function provide_invalid_range(): array
    {
        $etag = uniqid();

        return [

            [ [ ], 10000, uniqid() ],
            [ [ 'Range' => 'bytes=1-10', 'If-Range' => uniqid() ], 10000, uniqid() ],
            [ [ 'Range' => 'bytes', 'If-Range' => $etag ], 10000, $etag ],
            [ [ 'Range' => '0-499', 'If-Range' => $etag ], 10000, $etag ],
            [ [ 'Range' => 'bytes=-', 'If-Range' => $etag ], 10000, $etag ],
            'last position before first' => [ [ 'Range' => 'bytes=999-500' ], 10000, $etag ],
            'multiple ranges' => [ [ 'Range' => 'bytes=0-99,200-299' ], 10000, $etag ],
            'weak If-Range' => [ [ 'Range' => 'bytes=0-99', 'If-Range' => "W/$etag" ], 10000, $etag ],

        ];
    }

    #[DataProvider('provide_unsatisfiable')]
    public function test_should_be_unsatisfiable(string $range): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertFalse(RequestRange::from($headers, 10000, $etag)->is_satisfiable);
    }

    public static function provide_unsatisfiable(): array
    {
        return [

            [ 'bytes=11000-' ],
            [ 'bytes=10000-' ],
            [ 'bytes=10000-10001' ],
            [ 'bytes=-0' ],

        ];
    }

    #[DataProvider('provide_valid_range')]
    public function test_should_return_range(string $range, string $expected): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);
        $range = RequestRange::from($headers, 10000, $etag);

        $this->assertTrue($range->is_satisfiable);
        $this->assertEquals($expected, (string) $range);
    }

    public static function provide_valid_range(): array
    {
        return [

            [ 'bytes=0-499', 'bytes 0-499/10000' ],
            [ 'bytes=500-999', 'bytes 500-999/10000' ],
            [ 'bytes=-500', 'bytes 9500-9999/10000' ],
            [ 'bytes=9500-', 'bytes 9500-9999/10000' ],
            'first byte' => [ 'bytes=0-0', 'bytes 0-0/10000' ],
            'last byte' => [ 'bytes=9999-9999', 'bytes 9999-9999/10000' ],
            'last position clamped' => [ 'bytes=0-999999', 'bytes 0-9999/10000' ],
            'last position clamped, with offset' => [ 'bytes=9500-20000', 'bytes 9500-9999/10000' ],
            'suffix larger than the file' => [ 'bytes=-11000', 'bytes 0-9999/10000' ],

        ];
    }

    #[DataProvider('provide_test_is_total')]
    public function test_is_total($range, $expected): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->is_total);
    }

    public static function provide_test_is_total(): array
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
    public function test_length($range, $expected): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->length);
    }

    public static function provide_test_length(): array
    {
        return [

            [ 'bytes=0-499', 500 ],
            [ 'bytes=500-999', 500 ],
            [ 'bytes=-500', 500 ],
            [ 'bytes=9500-', 500 ],
            [ 'bytes=0-9999', 10000 ],
            [ 'bytes=0-0', 1 ],
            [ 'bytes=-11000', 10000 ],

        ];
    }

    #[DataProvider('provide_test_max_length')]
    public function test_max_length($range, $expected): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->max_length);
    }

    public static function provide_test_max_length(): array
    {
        return [

            [ 'bytes=0-499', 500 ],
            [ 'bytes=500-999', 500 ],
            [ 'bytes=-500', 500 ],
            [ 'bytes=9500-', 500 ],
            [ 'bytes=0-9999', 10000 ],
            [ 'bytes=0-12000', 10000 ]

        ];
    }

    #[DataProvider('provide_test_offset')]
    public function test_offset($range, $expected): void
    {
        $etag = uniqid();
        $headers = new Headers([ 'Range' => $range, 'If-Range' => $etag ]);

        $this->assertEquals($expected, RequestRange::from($headers, 10000, $etag)->offset);
    }

    public static function provide_test_offset(): array
    {
        return [

            [ 'bytes=0-499', 0 ],
            [ 'bytes=500-999', 500 ],
            [ 'bytes=-500', 9500 ],
            [ 'bytes=9500-', 9500 ],
            [ 'bytes=0-9999', 0 ]

        ];
    }

    public function test_empty_representation_is_unsatisfiable(): void
    {
        foreach ([ 'bytes=0-', 'bytes=0-0', 'bytes=-1' ] as $range) {
            $this->assertFalse(RequestRange::from(new Headers([ 'Range' => $range ]), 0, '"x"')->is_satisfiable, $range);
        }
    }

    #[DataProvider('provide_test_if_range_with_date')]
    public function test_if_range_with_date(string $if_range, ?int $last_modified, bool $expected): void
    {
        $headers = new Headers([ 'Range' => 'bytes=0-99', 'If-Range' => $if_range ]);

        $this->assertSame($expected, RequestRange::from($headers, 10000, '"etag"', $last_modified) !== null);
    }

    public static function provide_test_if_range_with_date(): array
    {
        $last_modified = gmmktime(8, 49, 37, 11, 6, 1994);
        $date = 'Sun, 06 Nov 1994 08:49:37 GMT';

        return [

            'same date' => [ $date, $last_modified, true ],
            'modified since' => [ $date, $last_modified + 1, false ],
            'unknown modification time' => [ $date, null, false ],
            'not an HTTP date' => [ '1994-11-06 08:49:37', $last_modified, false ],
            'entity tag' => [ '"etag"', $last_modified, true ],

        ];
    }
}
