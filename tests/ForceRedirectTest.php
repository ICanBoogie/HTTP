<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\ForceRedirect;
use ICanBoogie\HTTP\Status;
use PHPUnit\Framework\TestCase;

final class ForceRedirectTest extends TestCase
{
    public function test_get_location(): void
    {
        $location = '/to/location.html';
        $exception = new ForceRedirect($location);
        $this->assertEquals(Status::FOUND, $exception->getCode());
        $this->assertEquals($location, $exception->location);
    }
}
