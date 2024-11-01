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

use PHPUnit\Framework\Attributes\DataProvider;

class FileInfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $pathname
     * @param $expected
     */
    #[DataProvider('provide_test_resolve_type')]
    public function test_resolve_type($pathname, $expected)
    {
        $this->assertEquals($expected, FileInfo::resolve_type($pathname));
    }

    public static function provide_test_resolve_type()
    {
        $bytes = create_file();

        return [

            [ $bytes, 'application/octet-stream' ],
            [ __DIR__ . '/../composer.json', 'application/json' ],
            [ __DIR__ . '/../LICENSE', 'text/plain' ]

        ];
    }
}
