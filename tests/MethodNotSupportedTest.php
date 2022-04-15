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

use ICanBoogie\HTTP\MethodNotSupported;
use PHPUnit\Framework\TestCase;

final class MethodNotSupportedTest extends TestCase
{
    public function test_get_method()
    {
        $method = 'UNSUPPORTED';
        $exception = new MethodNotSupported($method);
        $this->assertEquals($method, $exception->method);
    }
}
