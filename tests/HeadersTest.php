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

class HeadersTest extends \PHPUnit_Framework_TestCase
{
	public function testDateTimeFromDateTime()
	{
		$datetime = new \DateTime();
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
		$headers = new Headers();
		$this->assertInstanceOf('ICanBoogie\HTTP\CacheControlHeader', $headers['Cache-Control']);
		$headers['Cache-Control'] = 'public, max-age=3600, no-transform';
		$this->assertInstanceOf('ICanBoogie\HTTP\CacheControlHeader', $headers['Cache-Control']);
		$this->assertEquals('public', $headers['Cache-Control']->cacheable);
		$this->assertEquals('3600', $headers['Cache-Control']->max_age);
		$this->assertTrue($headers['Cache-Control']->no_transform);
	}

	public function testContentDisposition()
	{
		$headers = new Headers;
		$this->assertInstanceOf('ICanBoogie\HTTP\ContentDispositionHeader', $headers['Content-Disposition']);
		$headers['Content-Disposition'] = "attachment; filename=test.txt";
		$this->assertInstanceOf('ICanBoogie\HTTP\ContentDispositionHeader', $headers['Content-Disposition']);
		$this->assertEquals('attachment', $headers['Content-Disposition']->type);
		$this->assertEquals('test.txt', $headers['Content-Disposition']->filename);
	}

	public function testContentType()
	{
		$headers = new Headers;
		$this->assertInstanceOf('ICanBoogie\HTTP\ContentTypeHeader', $headers['Content-Type']);
		$headers['Content-Type'] = 'text/plain; charset=iso-8859-1';
		$this->assertInstanceOf('ICanBoogie\HTTP\ContentTypeHeader', $headers['Content-Type']);
		$this->assertEquals('text/plain', $headers['Content-Type']->type);
		$this->assertEquals('iso-8859-1', $headers['Content-Type']->charset);
	}
}