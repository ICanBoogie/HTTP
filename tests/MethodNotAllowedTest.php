<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\MethodNotAllowed;
use PHPUnit\Framework\TestCase;

final class MethodNotAllowedTest extends TestCase
{
    public function test_get_method(): void
    {
        $method = 'UNSUPPORTED';
        $exception = new MethodNotAllowed($method);
        $this->assertEquals($method, $exception->method);
    }
}
