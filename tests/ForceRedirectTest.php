<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\ForceRedirect;
use ICanBoogie\HTTP\ResponseStatus;
use PHPUnit\Framework\TestCase;

final class ForceRedirectTest extends TestCase
{
    public function test_get_location(): void
    {
        $location = '/to/location.html';
        $exception = new ForceRedirect($location);
        $this->assertEquals(ResponseStatus::STATUS_FOUND, $exception->getCode());
        $this->assertEquals($location, $exception->location);
    }
}
