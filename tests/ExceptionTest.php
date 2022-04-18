<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\Exception;
use ICanBoogie\HTTP\ForceRedirect;
use ICanBoogie\HTTP\MethodNotAllowed;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\ServiceUnavailable;
use ICanBoogie\HTTP\StatusCodeNotValid;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ExceptionTest extends TestCase
{
    /**
     * @dataProvider provide_test_implements
     */
    public function test_implements($class, $args)
    {
        $reflection = new ReflectionClass($class);
        $exception = $reflection->newInstanceArgs($args);

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function provide_test_implements(): array
    {
        return [

            [ NotFound::class, [] ],
            [ ServiceUnavailable::class, [] ],
            [ MethodNotAllowed::class, [ 'UNSUPPORTED' ] ],
            [ StatusCodeNotValid::class, [ 123 ] ],
            [ ForceRedirect::class, [ 'to/location.html' ] ],

        ];
    }
}
