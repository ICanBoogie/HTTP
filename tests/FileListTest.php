<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\File;
use ICanBoogie\HTTP\FileList;
use PHPUnit\Framework\TestCase;

class FileListTest extends TestCase
{
    public function test_from_self_should_return_clone(): void
    {
        $files1 = new FileList();
        $files2 = FileList::from($files1);

        $this->assertInstanceOf(FileList::class, $files2);
        $this->assertNotSame($files1, $files2);
    }

    public function test_should_return_null_if_offset_not_defined(): void
    {
        $files = new FileList();
        $this->assertNull($files['undefined']);
    }

    public function test_should_create_instance_by_setting_offset(): void
    {
        $files = new FileList();
        $files['one'] = [ 'pathname' => __FILE__ ];

        $this->assertInstanceOf(File::class, $files['one']);
        $this->assertEquals(__FILE__, $files['one']->pathname);
    }

    public function test_should_remove_offset(): void
    {
        $files = new FileList();
        $files['one'] = [ 'pathname' => __FILE__ ];
        $this->assertNotNull($files['one']);
        unset($files['one']);
        $this->assertNull($files['one']);
    }

    public function test_should_iterate(): void
    {
        $expected = [

            [ 'pathname' => __DIR__ . '/PermissionRequiredTest.php' ],
            [ 'pathname' => __DIR__ . '/ExceptionTest.php' ],
            [ 'pathname' => __DIR__ . '/FileListTest.php' ]

        ];

        $files = new FileList($expected);
        $list = [];

        foreach ($files as $file) {
            $this->assertInstanceOf(File::class, $file);
            $list[] = [ 'pathname' => $file->pathname ];
        }

        $this->assertEquals($expected, $list);
    }
}
