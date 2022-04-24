<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder\DelegateToClosure;
use ICanBoogie\HTTP\Response;
use PHPUnit\Framework\TestCase;

final class DelegateToClosureTest extends TestCase
{
    public function test_respond(): void
    {
        $request = Request::from([]);
        $response = new Response();

        $responder = new DelegateToClosure(function (Request $actual) use ($request, $response): Response {
            $this->assertSame($request, $actual);

            return $response;
        });

        $this->assertSame($response, $responder->respond($request));
    }
}
