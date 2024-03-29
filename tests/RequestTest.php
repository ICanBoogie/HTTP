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

use ICanBoogie\HTTP\File;
use ICanBoogie\HTTP\FileList;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;
use ICanBoogie\HTTP\RequestOptions;
use ICanBoogie\PropertyNotWritable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private static Request $request;

    public static function setupBeforeClass(): void
    {
        self::$request = Request::from($_SERVER);
    }

    public function test_clone(): void
    {
        $request = Request::from($_SERVER);
        $clone = clone $request;

        $this->assertNotSame($request->headers, $clone->headers);
        $this->assertNotSame($request->context, $clone->context);
    }

    /**
     * @dataProvider provide_test_write_readonly_properties
     */
    public function test_write_readonly_properties(string $property): void
    {
        $this->expectException(PropertyNotWritable::class);

        self::$request->$property = null;
    }

    public function provide_test_write_readonly_properties(): array
    {
        $properties = 'authorization content_length context extension ip'
        . ' is_local is_xhr'
        . ' normalized_path method path port query_string referer script_name uri'
        . ' user_agent files';

        return array_map(function ($name) {
            return (array) $name;
        }, explode(' ', $properties));
    }

    public function test_from_with_cache_control(): void
    {
        $value = "public, must-revalidate";
        $request = Request::from([ RequestOptions::OPTION_CACHE_CONTROL => $value ]);
        $this->assertFalse(isset($request->cache_control));
        $this->assertEquals('public', $request->headers->cache_control->cacheable);
        $this->assertTrue($request->headers->cache_control->must_revalidate);
    }

    public function test_from_with_content_length(): void
    {
        $value = 123456789;
        $request = Request::from([ RequestOptions::OPTION_CONTENT_LENGTH => $value ]);
        $this->assertFalse(isset($request->content_length));
        $this->assertEquals($value, $request->content_length);
    }

    public function test_from_with_ip(): void
    {
        $value = '192.168.13.69';
        $request = Request::from([ RequestOptions::OPTION_IP => $value ]);
        $this->assertFalse(isset($request->ip));
        $this->assertEquals($value, $request->ip);
    }

    public function test_from_with_forwarded_ip(): void
    {
        $value = '192.168.13.69';
        $request = Request::from([ RequestOptions::OPTION_HEADERS => [

            'X-Forwarded-For' => "$value,::1"

        ] ]);

        $this->assertEquals($value, $request->ip);
    }

    public function test_from_with_is_local(): void
    {
        $request = Request::from([ RequestOptions::OPTION_IS_LOCAL => true ]);
        $this->assertFalse(isset($request->is_local));
        $this->assertTrue($request->is_local);

        $request = Request::from([ RequestOptions::OPTION_IS_LOCAL => false ]);
        $this->assertFalse(isset($request->is_local));
        $this->assertTrue($request->is_local); // yes is_local is `true` even if it was defined as `false`, that's because IP is not defined.
    }

    public function test_from_with_is_xhr(): void
    {
        $request = Request::from([ RequestOptions::OPTION_IS_XHR => true ]);
        $this->assertFalse(isset($request->is_xhr));
        $this->assertTrue($request->is_xhr);

        $request = Request::from([ RequestOptions::OPTION_IS_XHR => false ]);
        $this->assertFalse(isset($request->is_xhr));
        $this->assertFalse($request->is_xhr);
    }

    public function test_from_with_method(): void
    {
        $request = Request::from([ RequestOptions::OPTION_METHOD => RequestMethod::METHOD_OPTIONS ]);
        $this->assertFalse(isset($request->method));
        $this->assertEquals(RequestMethod::METHOD_OPTIONS, $request->method);
    }

    public function test_from_with_emulated_method(): void
    {
        $request = Request::from([

            RequestOptions::OPTION_METHOD => RequestMethod::METHOD_POST,
            RequestOptions::OPTION_REQUEST_PARAMS => [ '_method' => RequestMethod::METHOD_DELETE ]

        ]);

        $this->assertEquals(RequestMethod::METHOD_DELETE, $request->method);
    }

    public function test_from_with_path(): void
    {
        $request = Request::from([ RequestOptions::OPTION_PATH => '/path/' ]);
        $this->assertFalse(isset($request->path));
        $this->assertEquals('/path/', $request->path);

        $request = Request::from('/path/');
        $this->assertFalse(isset($request->path));
        $this->assertEquals('/path/', $request->path);
    }

    public function test_from_with_referer(): void
    {
        $value = 'https://example.org/referer/';
        $request = Request::from([ RequestOptions::OPTION_REFERER => $value ]);
        $this->assertFalse(isset($request->referer));
        $this->assertEquals($value, $request->referer);
    }

    public function test_from_with_uri(): void
    {
        $value = '/uri/';
        $request = Request::from([ RequestOptions::OPTION_URI => $value ]);
        $this->assertFalse(isset($request->uri));
        $this->assertEquals($value, $request->uri);

        $request = Request::from($value);
        $this->assertFalse(isset($request->uri));
        $this->assertEquals($value, $request->uri);
    }

    public function test_from_with_uri_and_query_string(): void
    {
        $param1 = 1;
        $param2 = "\\a";
        $param3 = "L'été est là";

        $path = '/uri/';
        $query_string = http_build_query([ 'p1' => $param1, 'p2' => $param2, 'p3' => $param3 ]);
        $uri = "$path?$query_string";
        $request = Request::from($uri);
        $this->assertFalse(isset($request->uri));
        $this->assertEquals($uri, $request->uri);
        $this->assertEquals($path, $request->path);
        $this->assertEquals($query_string, $request->query_string);
        $this->assertArrayHasKey('p1', $request->query_params);
        $this->assertArrayHasKey('p2', $request->query_params);
        $this->assertArrayHasKey('p3', $request->query_params);
        $this->assertArrayHasKey('p1', $request->params);
        $this->assertArrayHasKey('p2', $request->params);
        $this->assertArrayHasKey('p3', $request->params);
        $this->assertEquals($param1, $request->query_params['p1']);
        $this->assertEquals($param2, $request->query_params['p2']);
        $this->assertEquals($param3, $request->query_params['p3']);
    }

    public function test_from_with_user_agent(): void
    {
        $request = Request::from([ RequestOptions::OPTION_USER_AGENT => 'Madonna' ]);
        $this->assertFalse(isset($request->user_agent));
        $this->assertEquals('Madonna', $request->user_agent);
    }

    public function test_from_with_files(): void
    {
        $request = Request::from('/path/to/file');
        $this->assertInstanceOf(FileList::class, $request->files);
        $this->assertEquals(0, $request->files->count());

        $request = Request::from([

            RequestOptions::OPTION_FILES => [

                'one' => [ 'pathname' => __FILE__ ],
                'two' => [ 'pathname' => __FILE__ ]

            ]

        ]);

        $this->assertInstanceOf(FileList::class, $request->files);
        $this->assertEquals(2, $request->files->count());

        foreach ([ 'one', 'two' ] as $id) {
            $this->assertInstanceOf(File::class, $request->files[$id]);
            $this->assertEquals(__FILE__, $request->files[$id]->pathname);
        }
    }

    public function test_from_with_headers(): void
    {
        $request = Request::from([

            RequestOptions::OPTION_URI => '/path/to/file',
            RequestOptions::OPTION_HEADERS => [

                "Cache-Control" => "max-age=0",
                "Accept" => "application/json"

            ]

        ]);

        $this->assertInstanceOf(Headers::class, $request->headers);
        $this->assertEquals("max-age=0", (string) $request->headers->cache_control);
        $this->assertEquals("application/json", (string) $request->headers['Accept']);

        $headers = new Headers([

            "Cache-Control" => "max-age=0",
            "Accept" => "application/json"

        ]);

        $request = Request::from([

            RequestOptions::OPTION_URI => '/path/to/file',
            RequestOptions::OPTION_HEADERS => $headers

        ]);

        $this->assertSame($headers, $request->headers);
    }

    /**
     * @dataProvider provide_test_get_is_local
     */
    public function test_get_is_local(string $ip, bool $expected): void
    {
        $request = Request::from([ RequestOptions::OPTION_IP => $ip ]);
        $this->assertEquals($expected, $request->is_local);
    }

    public function provide_test_get_is_local(): array
    {
        return [

            [ '::1', true ],
            [ '127.0.0.1', true ],
            [ '127.0.0.255', true ],
            [ '0:0:0:0:0:0:0:1', true ],
            [ '192.168.0.1', false ],
            [ '0:0:0:0:0:0:0:2', false ]

        ];
    }

    public function test_get_script_name(): void
    {
        $expected = __FILE__;
        $request = Request::from([], [ 'SCRIPT_NAME' => $expected ]);
        $this->assertEquals($expected, $request->script_name);
    }

    /**
     * @dataProvider provide_test_get_authorization
     */
    public function test_get_authorization(array $env, string|null $expected): void
    {
        $request = Request::from([], $env);
        $this->assertEquals($expected, $request->authorization);
    }

    public function provide_test_get_authorization(): array
    {
        $ex1 = uniqid();
        $ex2 = uniqid();
        $ex3 = uniqid();
        $ex4 = uniqid();

        return [

            [ [ 'HTTP_AUTHORIZATION' => $ex1 ], $ex1 ],
            [ [ 'X-HTTP_AUTHORIZATION' => $ex2 ], $ex2 ],
            [ [ 'X_HTTP_AUTHORIZATION' => $ex3 ], $ex3 ],
            [ [ 'REDIRECT_X_HTTP_AUTHORIZATION' => $ex4 ], $ex4 ],
            [ [  ], null ]

        ];
    }

    public function test_get_port(): void
    {
        $expected = '1234';
        $request = Request::from([], [ 'REQUEST_PORT' => $expected ]);
        $this->assertEquals($expected, $request->port);
    }

    public function test_get_normalized_path(): void
    {
        $expected = '/';
        $request = Request::from('/index.php');
        $this->assertEquals($expected, $request->normalized_path);
    }

    public function test_get_extension(): void
    {
        $request = Request::from('/cat.gif');
        $this->assertEquals('gif', $request->extension);
    }

    public function test_query_string_from_uri(): void
    {
        $uri = '/api/users/login';
        $query = 'redirect_to=heaven';
        $request = Request::from($uri, [ 'QUERY_STRING' => $query ]);
        $this->assertEmpty($request->query_string);
        $this->assertEquals($uri, $request->uri);
        $this->assertEquals($uri, $request->path);

        $request = Request::from($uri . '?' . $query);
        $this->assertEquals($query, $request->query_string);
        $this->assertEquals($uri . '?' . $query, $request->uri);
        $this->assertEquals($uri, $request->path);
        $this->assertArrayHasKey('redirect_to', $request->query_params);
        $this->assertArrayHasKey('redirect_to', $request->params);
        $this->assertEquals('heaven', $request->query_params['redirect_to']);
        $this->assertEquals('heaven', $request->params['redirect_to']);
    }

    public function test_path_when_uri_is_missing_query_string(): void
    {
        $request = Request::from([], [ 'QUERY_STRING' => 'redirect_to=the-moon', 'REQUEST_URI' => '/api/users/login' ]);
        $this->assertEquals('redirect_to=the-moon', $request->query_string);
        $this->assertEquals('/api/users/login', $request->uri);
        $this->assertEquals('/api/users/login', $request->path);
    }

    public function test_params(): void
    {
        $request = Request::from([

            RequestOptions::OPTION_PATH_PARAMS => [

                'p1' => 1,
                'p2' => 2

            ],

            RequestOptions::OPTION_REQUEST_PARAMS => [

                'p1' => 10,
                'p2' => 20,
                'p3' => 3

            ],

            RequestOptions::OPTION_QUERY_PARAMS => [

                'p1' => 100,
                'p2' => 200,
                'p3' => 300,
                'p4' => 4

            ]

        ]);

        $this->assertSame([ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ], $request->params);

        $request->request_params['p5'] = 5;

        $expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ];
        $this->assertSame($expected, $request->params);
        $request->params['p5'] = 5;
        $expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4, 'p5' => 5 ];
        $this->assertEquals($expected, $request->params);

        unset($request->params['p5']);
        $expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ];
        $this->assertEquals($expected, $request->params);
    }

    /**
     * @dataProvider provide_test_change
     *
     * @param array<RequestOptions::*, mixed> $properties
     */
    public function test_change(array $properties): void
    {
        static $iterated;

        if (!$iterated) {
            $iterated = Request::from();
        }

        $changed = $iterated->with($properties);

        $this->assertNotSame($changed, $iterated);

        foreach ($properties as $property => $value) {
            $this->assertEquals($value, $changed->$property);
        }
    }

    public function provide_test_change(): array
    {
        return [

            [ [ RequestOptions::OPTION_IS_XHR => true ] ],
            [ [ RequestOptions::OPTION_IS_XHR => false ] ],
            [ [ RequestOptions::OPTION_METHOD => RequestMethod::METHOD_CONNECT ] ],
            [ [ RequestOptions::OPTION_URI => '/path/to/something' ] ],
            [ [ RequestOptions::OPTION_URI => '/path/to/something-else' ] ],

        ];
    }

    public function test_change_with_previous_params(): void
    {
        $request1 = Request::from([

            RequestOptions::OPTION_REQUEST_PARAMS => [ 'rp1' => 'one', 'rp2' => 'two' ],
            RequestOptions::OPTION_QUERY_PARAMS => [ 'qp1' => 'one' ],
            RequestOptions::OPTION_PATH_PARAMS => [ 'pp1' => 'one' ]

        ]);

        $this->assertSame([ 'pp1' => 'one', 'rp1' => 'one', 'rp2' => 'two', 'qp1' => 'one' ], $request1->params);

        $request2 = $request1->with([

            RequestOptions::OPTION_REQUEST_PARAMS => [],
            RequestOptions::OPTION_PATH_PARAMS => [ 'pp2' => 'two' ]

        ]);

        $this->assertSame([ ], $request2->request_params);
        $this->assertSame([ 'pp2' => 'two' ], $request2->path_params);
        $this->assertSame([ 'pp2' => 'two', 'qp1' => 'one' ], $request2->params);

        $request3 = $request2->with([

            RequestOptions::OPTION_QUERY_PARAMS => [],
            RequestOptions::OPTION_PATH_PARAMS => []

        ]);

        $this->assertSame([ ], $request3->request_params);
        $this->assertSame([ ], $request3->query_params);
        $this->assertSame([ ], $request3->path_params);
        $this->assertSame([ ], $request3->params);
    }

    public function test_should_throw_exception_when_changing_with_unsupported_property(): void
    {
        $request = Request::from();

        $this->expectException(InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        $request->with([ 'unsupported_property' => uniqid() ]);
    }
}
