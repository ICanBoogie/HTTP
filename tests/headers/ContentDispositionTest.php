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

class ContentDispositionTest extends \PHPUnit_Framework_TestCase
{
	/**
     * @dataProvider provider_from
     */
	public function test_from($source, $values)
	{
		$h = ContentDispositionHeader::from($source);

		$this->assertInstanceOf('ICanBoogie\HTTP\ContentDispositionHeader', $h);
		$this->assertEquals($values[0], $h->value);
		$this->assertEquals($values[1], (string) $h->filename);
		$this->assertEquals($values[1], $h->filename);
		$this->assertEquals($values[2], $h['filename']->charset);
		$this->assertEquals($values[3], $h['filename']->language);

		$this->assertEquals($source, (string) $h);
	}

	public function provider_from()
	{
		return array
		(
			array
			(
				'attachment; filename="file name.ext"', array
				(
					'attachment',
					'file name.ext',
					'ASCII',
					null
				)
			),

			array
			(
				'attachment; filename=file_name.ext', array
				(
					'attachment',
					'file_name.ext',
					'ASCII',
					null
				)
			),

			array
			(
				'attachment; filename="Naive file.txt"; filename*=UTF-8\'\'Na%C3%AFve%20file.txt', array
				(
					'attachment',
					'Naïve file.txt',
					'UTF-8',
					null
				)
			),

			array
			(
				'attachment; filename="Naive file.txt"; filename*=UTF-8\'en\'Na%C3%AFve%20file.txt', array
				(
					'attachment',
					'Naïve file.txt',
					'UTF-8',
					'en'
				)
			)
		);
	}

	public function test_value()
	{
		$cd = new ContentDispositionHeader;
		$this->assertNull($cd->value);
		$this->assertEquals('', (string) $cd);

		$cd->type = 'attachment';
		$this->assertEquals('attachment', (string) $cd);
	}

	public function test_value_alias()
	{
		$cd = new ContentDispositionHeader;

		$this->assertNull($cd->type);
		$this->assertNull($cd->value);

		$cd->type = 'inline';
		$this->assertEquals('inline', $cd->type);
		$this->assertEquals('inline', $cd->value);

		$cd->value = 'attachment';
		$this->assertEquals('attachment', $cd->type);
		$this->assertEquals('attachment', $cd->value);
	}

	public function test_attributes()
	{
		$cd = new ContentDispositionHeader;
		$this->assertInstanceOf('ICanBoogie\HTTP\HeaderParameter', $cd['filename']);
		$this->assertNull($cd->filename);

		$cd->type = 'inline';
		$this->assertEquals('inline', (string) $cd);

		$cd->filename = 'madonna.mp3';
		$this->assertEquals('inline; filename=madonna.mp3', (string) $cd);
		unset($cd['filename']);
		$this->assertInstanceOf('ICanBoogie\HTTP\HeaderParameter', $cd['filename']);
		$this->assertEquals('inline', (string) $cd);

		$cd->filename = 'madonna.mp3';
		$this->assertEquals('inline; filename=madonna.mp3', (string) $cd);
		unset($cd->filename);
		$this->assertInstanceOf('ICanBoogie\HTTP\HeaderParameter', $cd['filename']);
		$this->assertEquals('inline', (string) $cd);
	}
}