<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\StatusCodeNotValid;
use PHPUnit\Framework\TestCase;

class StatusCodeNotValidTest extends TestCase
{
    public function test_get_status_code()
    {
        $status_code = 123;
        $exception = new StatusCodeNotValid($status_code);
        $this->assertEquals($status_code, $exception->status_code);
    }
}
