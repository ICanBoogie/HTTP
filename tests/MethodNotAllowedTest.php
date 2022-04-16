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

use ICanBoogie\HTTP\MethodNotAllowed;
use PHPUnit\Framework\TestCase;

final class MethodNotAllowedTest extends TestCase
{
    public function test_get_method()
    {
        $method = 'UNSUPPORTED';
        $exception = new MethodNotAllowed($method);
        $this->assertEquals($method, $exception->method);
    }
}
