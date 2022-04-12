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

use ICanBoogie\FormattedString;
use ICanBoogie\PropertyNotWritable;
use PHPUnit\Framework\TestCase;

use function uniqid;

use const UPLOAD_ERR_CANT_WRITE;

class FileTest extends TestCase
{
    /**
     * @dataProvider provide_test_get_extension
     */
    public function test_get_extension($expected, $pathname)
    {
        $file = File::from([ File::OPTION_PATHNAME => $pathname ]);

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
        $file = File::from([ File::OPTION_PATHNAME => '/path/to/example.zip' ]);

        $this->assertEquals($expected, $file->match($against));
    }

    public function provide_test_match()
    {
        return [

            [ true,  null ],
            [ true,  false ],
            [ true,  '' ],
            [ true,  [] ],
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
        $file = File::from([ File::OPTION_ERROR => $error ]);

        $this->assertEquals($error, $file->error);

        if ($expected === null) {
            $this->assertNull($file->error_message);
        } else {
            $message = $file->error_message;

            $this->assertInstanceOf(FormattedString::class, $message);
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
            [ 123456,                "An error has occurred."]

        ];
    }

    /**
     * @dataProvider provide_test_to_array
     */
    public function test_to_array($properties, $expected)
    {
        $this->assertSame($expected, File::from($properties)->to_array());
    }

    public function provide_test_to_array()
    {
        return [

            [

                [

                    File::OPTION_PATHNAME => __FILE__

                ],

                [

                    'name' => basename(__FILE__),
                    'unsuffixed_name' => basename(__FILE__, '.php'),
                    'extension' => '.php',
                    'type' => 'application/x-php',
                    'size' => filesize(__FILE__),
                    'pathname' => __FILE__,
                    'error' => null,
                    'error_message' => null

                ]

            ],

            [

                [

                    File::OPTION_PATHNAME => '/path/to/image.png',
                    File::OPTION_SIZE => 1234

                ],

                [

                    'name' => 'image.png',
                    'unsuffixed_name' => 'image',
                    'extension' => '.png',
                    'type' => 'image/png',
                    'size' => 1234,
                    'pathname' => '/path/to/image.png',
                    'error' => null,
                    'error_message' => null

                ]

            ],

            [

                [

                    File::OPTION_ERROR => UPLOAD_ERR_NO_FILE

                ],

                [

                    'name' => null,
                    'unsuffixed_name' => null,
                    'extension' => null,
                    'type' => null,
                    'size' => false,
                    'pathname' => null,
                    'error' => UPLOAD_ERR_NO_FILE,
                    'error_message' => "No file was uploaded."

                ]

            ]

        ];
    }

    /**
     * @dataProvider provide_readonly_properties
     */
    public function test_write_readonly_properties(string $property)
    {
        $file = File::from([ File::OPTION_PATHNAME => __FILE__ ]);

        $this->expectException(PropertyNotWritable::class);

        $file->$property = null;
    }

    /**
     * @dataProvider provide_readonly_properties
     */
    public function test_read_readonly_properties(string $property)
    {
        $file = File::from([
            File::OPTION_PATHNAME => __FILE__,
            File::OPTION_ERROR => UPLOAD_ERR_CANT_WRITE,
        ]);

        $this->assertNotNull($file->$property);
    }

    public function provide_readonly_properties()
    {
        $properties = 'error error_message extension is_uploaded is_valid name pathname size type'
        . ' unsuffixed_name';

        return array_map(function ($v) {
            return (array) $v;
        }, explode(' ', $properties));
    }

    public function test_fake_file()
    {
        $sandbox = __DIR__ . DIRECTORY_SEPARATOR . 'sandbox' . DIRECTORY_SEPARATOR;
        $pathname = $sandbox . uniqid() . '.php';
        $destination = $sandbox . uniqid() . '00.php';

        copy(__FILE__, $pathname);

        $file = File::from([ File::OPTION_PATHNAME => $pathname ]);

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
        $this->assertFalse($file->size);
        $this->assertNull($file->extension);
        $this->assertNull($file->type);
        $this->assertNull($file->pathname);
        $this->assertNull($file->error);
        $this->assertFalse($file->is_valid);
        $this->assertFalse($file->is_uploaded);
    }

    public function test_should_get_defined_type()
    {
        $expected = 'application/x-bytes';

        $file = File::from([

            File::OPTION_PATHNAME => create_file(),
            File::OPTION_TYPE => $expected

        ]);

        $this->assertEquals($expected, $file->type);
    }

    public function test_should_get_defined_size()
    {
        $expected = 123456;

        $file = File::from([

            File::OPTION_PATHNAME => create_file(),
            File::OPTION_SIZE => $expected

        ]);

        $this->assertEquals($expected, $file->size);
    }

    public function test_move_overwrite()
    {
        $file1 = create_file();
        $file2 = create_file();

        $file = File::from($file1);

        $this->expectException(\Exception::class);

        $file->move($file2);
    }

    public function test_move_overwrite_force()
    {
        $file1 = create_file();
        $file2 = create_file();

        $expected = file_get_contents($file1);

        $file = File::from([ File::OPTION_PATHNAME => $file1 ]);
        $file->move($file2, File::MOVE_OVERWRITE);

        $this->assertFileDoesNotExist($file1);
        $this->assertFileExists($file2);
        $this->assertStringEqualsFile($file2, $expected);
    }
}
