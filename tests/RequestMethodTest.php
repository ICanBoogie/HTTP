<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\RequestMethod;
use PHPUnit\Framework\TestCase;

final class RequestMethodTest extends TestCase
{
    /**
     * @dataProvider provide_is
     */
    public function test_is(RequestMethod $method, string $is, bool $expected): void
    {
        $this->assertSame($expected, $method->{'is_' . $is}());
    }

    public function provide_is(): array
    {
        return [

            [ RequestMethod::METHOD_CONNECT, 'connect', true ],
            [ RequestMethod::METHOD_CONNECT, 'delete', false ],
            [ RequestMethod::METHOD_DELETE, 'delete', true ],
            [ RequestMethod::METHOD_DELETE, 'get', false ],
            [ RequestMethod::METHOD_GET, 'get', true ],
            [ RequestMethod::METHOD_GET, 'head', false ],
            [ RequestMethod::METHOD_HEAD, 'head', true ],
            [ RequestMethod::METHOD_HEAD, 'options', false ],
            [ RequestMethod::METHOD_OPTIONS, 'options', true ],
            [ RequestMethod::METHOD_OPTIONS, 'patch', false ],
            [ RequestMethod::METHOD_PATCH, 'patch', true ],
            [ RequestMethod::METHOD_PATCH, 'post', false ],
            [ RequestMethod::METHOD_POST, 'post', true ],
            [ RequestMethod::METHOD_POST, 'put', false ],
            [ RequestMethod::METHOD_PUT, 'put', true ],
            [ RequestMethod::METHOD_PUT, 'trace', false ],
            [ RequestMethod::METHOD_TRACE, 'trace', true ],
            [ RequestMethod::METHOD_TRACE, 'connect', false ],
            [ RequestMethod::METHOD_CONNECT, 'connect', true ],
            [ RequestMethod::METHOD_CONNECT, 'delete', false ],
            [ RequestMethod::METHOD_DELETE, 'delete', true ],
            [ RequestMethod::METHOD_DELETE, 'get', false ],
            [ RequestMethod::METHOD_GET, 'get', true ],
            [ RequestMethod::METHOD_GET, 'head', false ],
            [ RequestMethod::METHOD_HEAD, 'head', true ],
            [ RequestMethod::METHOD_HEAD, 'options', false ],
            [ RequestMethod::METHOD_OPTIONS, 'options', true ],
            [ RequestMethod::METHOD_OPTIONS, 'patch', false ],
            [ RequestMethod::METHOD_PATCH, 'patch', true ],
            [ RequestMethod::METHOD_PATCH, 'post', false ],
            [ RequestMethod::METHOD_POST, 'post', true ],
            [ RequestMethod::METHOD_POST, 'put', false ],
            [ RequestMethod::METHOD_PUT, 'put', true ],
            [ RequestMethod::METHOD_PUT, 'trace', false ],
            [ RequestMethod::METHOD_TRACE, 'trace', true ],
            [ RequestMethod::METHOD_TRACE, 'connect', false ],

            [ RequestMethod::METHOD_CONNECT, 'idempotent', false ],
            [ RequestMethod::METHOD_DELETE, 'idempotent', true ],
            [ RequestMethod::METHOD_GET, 'idempotent', true ],
            [ RequestMethod::METHOD_HEAD, 'idempotent', true ],
            [ RequestMethod::METHOD_OPTIONS, 'idempotent', true ],
            [ RequestMethod::METHOD_PATCH, 'idempotent', false ],
            [ RequestMethod::METHOD_POST, 'idempotent', false ],
            [ RequestMethod::METHOD_PUT, 'idempotent', true ],
            [ RequestMethod::METHOD_TRACE, 'idempotent', true ],

            [ RequestMethod::METHOD_CONNECT, 'safe', false ],
            [ RequestMethod::METHOD_DELETE, 'safe', false ],
            [ RequestMethod::METHOD_GET, 'safe', true ],
            [ RequestMethod::METHOD_HEAD, 'safe', true ],
            [ RequestMethod::METHOD_OPTIONS, 'safe', true ],
            [ RequestMethod::METHOD_PATCH, 'safe', false ],
            [ RequestMethod::METHOD_POST, 'safe', false ],
            [ RequestMethod::METHOD_PUT, 'safe', false ],
            [ RequestMethod::METHOD_TRACE, 'safe', true ],

        ];
    }
}
