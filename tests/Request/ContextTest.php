<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\Request;

use ICanBoogie\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function test_get_request(): void
    {
        $request = Request::from('/');
        $context = new Request\Context($request);
        $this->assertSame($request, $context->request);
    }

    public function test_set_dispatcher(): void
    {
        $something = new class () {
        };

        $context = new Request\Context(Request::from('/'));
        $context->add($something);
        $this->assertSame($something, $context->get($something::class));
    }
}
