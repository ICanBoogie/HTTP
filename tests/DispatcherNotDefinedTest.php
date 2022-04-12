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

class DispatcherNotDefinedTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance()
    {
        $dispatcher_id = uniqid();
        $instance = new DispatcherNotDefined($dispatcher_id);
        $this->assertSame($dispatcher_id, $instance->dispatcher_id);
    }
}
