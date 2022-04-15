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

use ICanBoogie\HTTP\AuthenticationRequired;
use ICanBoogie\HTTP\ResponseStatus;
use PHPUnit\Framework\TestCase;

final class AuthenticationRequiredTest extends TestCase
{
    public function test_message()
    {
        $exception = new AuthenticationRequired();

        $this->assertEquals("The requested URL requires authentication.", $exception->getMessage());
        $this->assertEquals(ResponseStatus::STATUS_UNAUTHORIZED, $exception->getCode());
    }
}
