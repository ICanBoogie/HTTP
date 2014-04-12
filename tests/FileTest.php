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
	public function test_get_extension($expected, $pathname)
	{
		$file = File::from([ 'pathname' => $pathname ]);

		$this->assertEquals($expected, $file->extension);
	}

	public function provide_test_get_extension()
	{
		return [

			[ '.c',        '/path/to/example.c' ],
			[ '.zip',      '/path/to/example.zip' ],
			[ '.document', '/path/to/example.document' ],
			[ '.png',      '/path/to/example.zip.png' ],
			[ '.gz',       '/path/to/example.tar.gz' ],
			[ '',          '/path/to/example' ]

		];
	}

	/**
	 * @dataProvider provide_test_match
	 */
	public function test_match($expected, $against)
	{
		$file = File::from([ 'pathname' => '/path/to/example.zip' ]);

		$this->assertEquals($expected, $file->match($against));
	}

	public function provide_test_match()
	{
		return [

			[ true,  '.zip' ],
			[ true,  'application/zip' ],
			[ true,  'application' ],
			[ true,  [ '.mp3', '.zip' ] ],
			[ true,  [ '.mp3', 'application' ] ],
			[ true,  [ '.mp3', 'application/zip' ] ],
			[ true,  [ '.zip', 'application/zip' ] ],
			[ false, '.png' ],
			[ false, 'image/png' ],
			[ false, 'image' ],
			[ false, [ '.mp3', '.png' ] ],
			[ false, [ '.mp3', 'image' ] ],
			[ false, [ '.mp3', 'image/png' ] ]

		];
	}

	/**
	 * @dataProvider provide_test_error_message
	 */
	public function test_error_message($error, $expected)
	{
		$file = File::from([ 'error' => $error ]);

		$this->assertEquals($error, $file->error);

		if ($expected === null)
		{
			$this->assertNull($file->error_message);
		}
		else
		{
			$message = $file->error_message;

			$this->assertInstanceOf('ICanBoogie\FormattedString', $message);
			$this->assertStringStartsWith($expected, (string) $message);
		}
	}

	public function provide_test_error_message()
	{
		return [

			[ UPLOAD_ERR_OK,         null ],
			[ UPLOAD_ERR_INI_SIZE,   "Maximum file size is" ],
			[ UPLOAD_ERR_FORM_SIZE,  "Maximum file size is" ],
			[ UPLOAD_ERR_PARTIAL,    "The uploaded file was only partially uploaded." ],
			[ UPLOAD_ERR_NO_FILE,    "No file was uploaded." ],
			[ UPLOAD_ERR_NO_TMP_DIR, "Missing a temporary folder." ],
			[ UPLOAD_ERR_CANT_WRITE, "Failed to write file to disk." ],
			[ UPLOAD_ERR_EXTENSION,  "A PHP extension stopped the file upload." ],
			[ 123456,                "An error has occured."]

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
		$properties = 'error error_message extension is_uploaded is_valid name pathname size type'
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