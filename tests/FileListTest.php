<?php

namespace ICanBoogie\HTTP;

class FileListTest extends \PHPUnit_Framework_TestCase
{
	public function test_from_self_should_return_clone()
	{
		$i1 = new FileList;
		$i2 = FileList::from($i1);

		$this->assertInstanceOf(FileList::class, $i2);
		$this->assertNotSame($i1, $i2);
	}

	public function test_should_return_null_if_offset_not_defined()
	{
		$i = new FileList;
		$this->assertNull($i['undefined']);
	}

	public function test_should_create_instance_by_setting_offset()
	{
		$i = new FileList;
		$i['one'] = [ 'pathname' => __FILE__ ];

		$this->assertInstanceOf(File::class, $i['one']);
		$this->assertEquals(__FILE__, $i['one']->pathname);
	}

	public function test_should_remove_offset()
	{
		$i = new FileList;
		$i['one'] = [ 'pathname' => __FILE__ ];
		$this->assertNotNull($i['one']);
		unset($i['one']);
		$this->assertNull($i['one']);
	}

	public function test_should_iterate()
	{
		$expected = [

			[ 'pathname' => __DIR__ . '/DispatcherTest.php' ],
			[ 'pathname' => __DIR__ . '/ExceptionTest.php' ],
			[ 'pathname' => __DIR__ . '/FileListTest.php' ]

		];

		$i = new FileList($expected);
		$list = [];

		foreach ($i as $file)
		{
			$this->assertInstanceOf(File::class, $file);
			$list[] = [ 'pathname' => $file->pathname ];
		}

		$this->assertEquals($expected, $list);
	}
}
