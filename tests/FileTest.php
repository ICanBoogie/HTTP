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

class FileTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_get_extension
	 */
	public function test_get_extension($expected, $properties)
	{
		$file = File::from($properties);

		$this->assertEquals($expected, $file->extension);
	}

	public function provide_test_get_extension()
	{
		return [

			[ '.c',        [ 'pathname' => '/path/to/example.c' ] ],
			[ '.zip',      [ 'pathname' => '/path/to/example.zip' ] ],
			[ '.document', [ 'pathname' => '/path/to/example.document' ] ],
			[ '.png',      [ 'pathname' => '/path/to/example.zip.png' ] ],
			[ '.gz',       [ 'pathname' => '/path/to/example.tar.gz' ] ],
			[ '',          [ 'pathname' => '/path/to/example' ] ]

		];
	}

	/**
	 * @dataProvider provide_test_match
	 */
	public function test_match($properties, $expected, $against)
	{
		$file = File::from($properties);

		$this->assertEquals($expected, $file->match($against));
	}

	public function provide_test_match()
	{
		return [

			[ [ 'pathname' => '/path/to/example.zip' ], true,  '.zip' ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  'application/zip' ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  'application' ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  [ '.mp3', '.zip' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  [ '.mp3', 'application' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  [ '.mp3', 'application/zip' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], true,  [ '.zip', 'application/zip' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, '.png' ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, 'image/png' ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, 'image' ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, [ '.mp3', '.png' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, [ '.mp3', 'image' ] ],
			[ [ 'pathname' => '/path/to/example.zip' ], false, [ '.mp3', 'image/png' ] ]

		];
	}

	/**
	 * @dataProvider provide_readonly_properties
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_write_readonly_properties($property)
	{
		$file = File::from([ 'pathname' => __FILE__ ]);
		$file->$property = null;
	}

	/**
	 * @dataProvider provide_readonly_properties
	 */
	public function test_read_readonly_properties($property)
	{
		$file = File::from([ 'pathname' => __FILE__ ]);
		$file->$property;
	}

	public function provide_readonly_properties()
	{
		$properties = 'error extension is_uploaded is_valid name pathname size type'
		. ' unsuffixed_name';

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	public function test_fake_file()
	{
		$sandbox = __DIR__ . DIRECTORY_SEPARATOR . 'sandbox' . DIRECTORY_SEPARATOR;
		$pathname = $sandbox . uniqid() . '.php';
		$destination = $sandbox . uniqid() . '00.php';

		copy(__FILE__, $pathname);

		$file = File::from([ 'pathname' => $pathname ]);

		$this->assertEquals($pathname, $file->pathname);
		$this->assertEquals(basename($pathname), $file->name);
		$this->assertEquals(basename($pathname, '.php'), $file->unsuffixed_name);
		$this->assertEquals(filesize($pathname), $file->size);
		$this->assertEquals(".php", $file->extension);
		$this->assertEquals('application/x-php', $file->type);
		$this->assertNull($file->error);
		$this->assertTrue($file->is_valid);
		$this->assertFalse($file->is_uploaded);

		$file->move($destination);
		$this->assertEquals($destination, $file->pathname);

		unlink($file->pathname);
	}

	public function test_empty_slot()
	{
		$file = File::from('example');

		$this->assertEquals('example', $file->name);
		$this->assertNull($file->size);
		$this->assertNull($file->extension);
		$this->assertNull($file->type);
		$this->assertNull($file->pathname);
		$this->assertNull($file->error);
		$this->assertFalse($file->is_valid);
		$this->assertFalse($file->is_uploaded);
	}
}