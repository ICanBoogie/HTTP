<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\ResponderProvider;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\ResponderProvider;
use ICanBoogie\HTTP\ResponderProvider\Chain;
use PHPUnit\Framework\TestCase;

final class ChainTest extends TestCase
{
    public function test_chain(): void
    {
        $responder = $this->createMock(Responder::class);

        $chain = new Chain([
            $this->makeProvider(),
            $this->makeProvider(),
            $this->makeProvider($responder),
            $this->makeProvider(shouldNotBeCalled: true),
        ]);

        $this->assertSame(
            $responder,
            $chain->responder_for_request(Request::from())
        );
    }

    public function test_chain_with_no_match(): void
    {
        $chain = new Chain([
            $this->makeProvider(),
            $this->makeProvider(),
            $this->makeProvider(),
        ]);

        $this->assertNull($chain->responder_for_request(Request::from()));
    }

    public function makeProvider(Responder $responder = null, bool $shouldNotBeCalled = false): ResponderProvider
    {
        $provider = $this->createMock(ResponderProvider::class);
        $provider
            ->expects($shouldNotBeCalled ? $this->never() : $this->once())
            ->method('responder_for_request')
            ->withAnyParameters()
            ->willReturn($responder);

        return $provider;
    }
}
