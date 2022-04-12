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

class FileInfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provide_test_resolve_type
     *
     * @param $pathname
     * @param $expected
     */
    public function test_resolve_type($pathname, $expected)
    {
        $this->assertEquals($expected, FileInfo::resolve_type($pathname));
    }

    public function provide_test_resolve_type()
    {
        $bytes = create_file();

        return [

            [ $bytes, 'application/octet-stream' ],
            [ __DIR__ . '/../composer.json', 'application/json' ],
            [ __DIR__ . '/../LICENSE', 'text/plain' ]

        ];
    }
}
