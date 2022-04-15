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

use ICanBoogie\HTTP\PermissionRequired;
use ICanBoogie\HTTP\ResponseStatus;
use PHPUnit\Framework\TestCase;

final class PermissionRequiredTest extends TestCase
{
    public function test_message(): void
    {
        $exception = new PermissionRequired();

        $this->assertEquals("You don't have the required permission.", $exception->getMessage());
        $this->assertEquals(ResponseStatus::STATUS_UNAUTHORIZED, $exception->getCode());
    }
}
