<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\PermissionRequired;
use ICanBoogie\HTTP\Status;
use PHPUnit\Framework\TestCase;

final class PermissionRequiredTest extends TestCase
{
    public function test_message(): void
    {
        $exception = new PermissionRequired();

        $this->assertEquals("You don't have the required permission.", $exception->getMessage());
        $this->assertEquals(Status::UNAUTHORIZED, $exception->getCode());
    }
}
