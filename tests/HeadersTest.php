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
use ICanBoogie\HTTP\Headers\Date as DateHeader;

class HeadersTest extends \PHPUnit_Framework_TestCase
{
	public function testDateTimeFromDateTime()
	{
		$datetime = new \DateTime;
		$headers_datetime = new DateHeader($datetime);
		$datetime->setTimezone(new \DateTimeZone('GMT'));

		$this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT', (string) $headers_datetime);
	}

	public function testDateTimeFromDateTimeString()
	{
		$datetime = new \DateTime('now', new \DateTimeZone('GMT'));

		$this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT'
		, (string) new DateHeader($datetime->format('D, d M Y H:i:s P')));

		$this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT'
		, (string) new DateHeader($datetime->format('D, d M Y H:i:s')));

		$this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT'
		, (string) new DateHeader($datetime->format('Y-m-d H:i:s')));
	}

	public function testCacheControl()
	{
		$headers = new Headers;
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\CacheControl', $headers['Cache-Control']);
		$headers['Cache-Control'] = 'public, max-age=3600, no-transform';
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\CacheControl', $headers['Cache-Control']);
		$this->assertEquals('public', $headers['Cache-Control']->cacheable);
		$this->assertEquals('3600', $headers['Cache-Control']->max_age);
		$this->assertTrue($headers['Cache-Control']->no_transform);
	}

	public function testContentDisposition()
	{
		$headers = new Headers;
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\ContentDisposition', $headers['Content-Disposition']);
		$headers['Content-Disposition'] = "attachment; filename=test.txt";
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\ContentDisposition', $headers['Content-Disposition']);
		$this->assertEquals('attachment', $headers['Content-Disposition']->type);
		$this->assertEquals('test.txt', $headers['Content-Disposition']->filename);
	}

	public function testContentType()
	{
		$headers = new Headers;
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\ContentType', $headers['Content-Type']);
		$headers['Content-Type'] = 'text/plain; charset=iso-8859-1';
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\ContentType', $headers['Content-Type']);
		$this->assertEquals('text/plain', $headers['Content-Type']->type);
		$this->assertEquals('iso-8859-1', $headers['Content-Type']->charset);
	}

	/**
	 * @dataProvider provide_test_date_header
	 */
	public function test_date_header($field, $value, $expected)
	{
		$headers = new Headers;

		if ($field !== 'Retry-After')
		{
			$this->assertInstanceOf('ICanBoogie\HTTP\Headers\Date', $headers[$field]);
		}

		$headers[$field] = $value;

		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\Date', $headers[$field]);
		$this->assertEquals($expected, (string) $headers[$field]);
	}

	public function provide_test_date_header()
	{
		$v1 = new \ICanBoogie\DateTime;
		$v2 = new \DateTime;
		$v3 = (string) $v1;
		$expected = $v1->utc->as_rfc1123;

		return [

			[ 'Date', $v1, $expected ],
			[ 'Date', $v2, $expected ],
			[ 'Date', $v3, $expected ],

			[ 'Expires', $v1, $expected ],
			[ 'Expires', $v2, $expected ],
			[ 'Expires', $v3, $expected ],

			[ 'If-Modified-Since', $v1, $expected ],
			[ 'If-Modified-Since', $v2, $expected ],
			[ 'If-Modified-Since', $v3, $expected ],

			[ 'If-Unmodified-Since', $v1, $expected ],
			[ 'If-Unmodified-Since', $v2, $expected ],
			[ 'If-Unmodified-Since', $v3, $expected ],

			[ 'Retry-After', $v1, $expected ],
			[ 'Retry-After', $v2, $expected ],
			[ 'Retry-After', $v3, $expected ]

		];
	}

	/**
	 * @dataProvider provide_test_empty_date
	 */
	public function test_empty_date($field)
	{
		$h = new Headers;

		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\Date', $h[$field]);
		$this->assertTrue($h[$field]->is_empty);
	}

	public function provide_test_empty_date()
	{
		return [

			[ 'Date' ],
			[ 'Expires' ],
			[ 'If-Modified-Since' ],
			[ 'If-Unmodified-Since' ]

		];
	}

	public function test_to_string_with_empty_dates()
	{
		$h = new Headers([

			'Content-Type' => 'text/plain'

		]);

		$this->assertTrue($h['Date']->is_empty);
		$this->assertTrue($h['Expires']->is_empty);
		$this->assertTrue($h['If-Modified-Since']->is_empty);
		$this->assertTrue($h['If-Unmodified-Since']->is_empty);

		$this->assertEquals("Content-Type: text/plain\r\n", (string) $h);
	}
}
