<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\AuthenticationRequired;
use ICanBoogie\HTTP\Status;
use PHPUnit\Framework\TestCase;

final class AuthenticationRequiredTest extends TestCase
{
    public function test_message()
    {
        $exception = new AuthenticationRequired();

        $this->assertEquals("The requested URL requires authentication.", $exception->getMessage());
        $this->assertEquals(Status::UNAUTHORIZED, $exception->getCode());
    }
}
