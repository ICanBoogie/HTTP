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

class StatusCodeNotValidTest extends \PHPUnit\Framework\TestCase
{
    public function test_get_status_code()
    {
        $status_code = 123;
        $exception = new StatusCodeNotValid($status_code);
        $this->assertEquals($status_code, $exception->status_code);
    }
}
