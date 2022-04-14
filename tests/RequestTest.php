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

use ICanBoogie\PropertyNotWritable;
use ICanBoogie\Prototype\MethodNotDefined;
use InvalidArgumentException;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    private static $request;

    public static function setupBeforeClass(): void
    {
        self::$request = Request::from($_SERVER);
    }

    public function test_clone()
    {
        $request = Request::from($_SERVER);
        $clone = clone $request;

        $this->assertNotSame($request->headers, $clone->headers);
        $this->assertNotSame($request->context, $clone->context);
    }

    /**
     * @dataProvider provide_test_write_readonly_properties
     */
    public function test_write_readonly_properties(string $property)
    {
        $this->expectException(PropertyNotWritable::class);

        self::$request->$property = null;
    }

    public function provide_test_write_readonly_properties()
    {
        $properties = 'authorization content_length cache_control context extension ip'
        . ' is_local is_xhr'
        . ' normalized_path method path port parent query_string referer script_name uri'
        . ' user_agent files';

        return array_map(function ($name) {
            return (array) $name;
        }, explode(' ', $properties));
    }

    public function test_from_with_cache_control()
    {
        $value = "public, must-revalidate";
        $request = Request::from([ Request::OPTION_CACHE_CONTROL => $value ]);
        $this->assertObjectNotHasAttribute('cache_control', $request);
        $this->assertInstanceOf(Headers\CacheControl::class, $request->cache_control);
        $this->assertEquals('public', $request->cache_control->cacheable);
        $this->assertTrue($request->cache_control->must_revalidate);
    }

    public function test_from_with_content_length()
    {
        $value = 123456789;
        $request = Request::from([ Request::OPTION_CONTENT_LENGTH => $value ]);
        $this->assertObjectNotHasAttribute('content_length', $request);
        $this->assertEquals($value, $request->content_length);
    }

    public function test_from_with_extension()
    {
        $this->expectException(InvalidArgumentException::class);
        Request::from([ 'extension' => '.png' ]);
    }

    public function test_from_with_ip()
    {
        $value = '192.168.13.69';
        $request = Request::from([ Request::OPTION_IP => $value ]);
        $this->assertObjectNotHasAttribute('ip', $request);
        $this->assertEquals($value, $request->ip);
    }

    public function test_from_with_forwarded_ip()
    {
        $value = '192.168.13.69';
        $request = Request::from([ Request::OPTION_HEADERS => [

            'X-Forwarded-For' => "$value,::1"

        ] ]);

        $this->assertEquals($value, $request->ip);
    }

    public function test_from_with_is_delete()
    {
        $request = Request::from([ Request::OPTION_IS_DELETE => true ]);
        $this->assertObjectNotHasAttribute('is_delete', $request);
        $this->assertEquals(RequestMethod::METHOD_DELETE, $request->method);
        $this->assertTrue($request->method->is_delete());

        $request = Request::from([ Request::OPTION_IS_DELETE => false ]);
        $this->assertObjectNotHasAttribute('is_delete', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_delete());
    }

    public function test_from_with_is_get()
    {
        $request = Request::from([ Request::OPTION_IS_GET => true ]);
        $this->assertObjectNotHasAttribute('is_get', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertTrue($request->method->is_get());

        $request = Request::from([ Request::OPTION_IS_GET => false ]);
        $this->assertObjectNotHasAttribute('is_get', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertTrue($request->method->is_get());
    }

    public function test_from_with_is_head()
    {
        $request = Request::from([ Request::OPTION_IS_HEAD => true ]);
        $this->assertObjectNotHasAttribute('is_head', $request);
        $this->assertEquals(RequestMethod::METHOD_HEAD, $request->method);
        $this->assertTrue($request->method->is_head());

        $request = Request::from([ Request::OPTION_IS_HEAD => false ]);
        $this->assertObjectNotHasAttribute('is_head', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_head());
    }

    public function test_from_with_is_local()
    {
        $request = Request::from([ Request::OPTION_IS_LOCAL => true ]);
        $this->assertObjectNotHasAttribute('is_local', $request);
        $this->assertTrue($request->is_local);

        $request = Request::from([ Request::OPTION_IS_LOCAL => false ]);
        $this->assertObjectNotHasAttribute('is_local', $request);
        $this->assertTrue($request->is_local); // yes is_local is `true` even if it was defined as `false`, that's because IP is not defined.
    }

    public function test_from_with_is_options()
    {
        $request = Request::from([ Request::OPTION_IS_OPTIONS => true ]);
        $this->assertObjectNotHasAttribute('is_options', $request);
        $this->assertEquals(RequestMethod::METHOD_OPTIONS, $request->method);
        $this->assertTrue($request->method->is_options());

        $request = Request::from([ Request::OPTION_IS_OPTIONS => false ]);
        $this->assertObjectNotHasAttribute('is_options', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_options());
    }

    public function test_from_with_is_patch()
    {
        $request = Request::from([ Request::OPTION_IS_PATCH => true ]);
        $this->assertObjectNotHasAttribute('is_patch', $request);
        $this->assertEquals(RequestMethod::METHOD_PATCH, $request->method);
        $this->assertTrue($request->method->is_patch());

        $request = Request::from([ Request::OPTION_IS_PATCH => false ]);
        $this->assertObjectNotHasAttribute('is_patch', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_patch());
    }

    public function test_from_with_is_post()
    {
        $request = Request::from([ Request::OPTION_IS_POST => true ]);
        $this->assertObjectNotHasAttribute('is_post', $request);
        $this->assertEquals(RequestMethod::METHOD_POST, $request->method);
        $this->assertTrue($request->method->is_post());

        $request = Request::from([ Request::OPTION_IS_POST => false ]);
        $this->assertObjectNotHasAttribute('is_post', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_post());
    }

    public function test_from_with_is_put()
    {
        $request = Request::from([ Request::OPTION_IS_PUT => true ]);
        $this->assertObjectNotHasAttribute('is_put', $request);
        $this->assertEquals(RequestMethod::METHOD_PUT, $request->method);
        $this->assertTrue($request->method->is_put());

        $request = Request::from([ Request::OPTION_IS_PUT => false ]);
        $this->assertObjectNotHasAttribute('is_put', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_put());
    }

    public function test_from_with_is_trace()
    {
        $request = Request::from([ Request::OPTION_IS_TRACE => true ]);
        $this->assertObjectNotHasAttribute('is_trace', $request);
        $this->assertEquals(RequestMethod::METHOD_TRACE, $request->method);
        $this->assertTrue($request->method->is_trace());

        $request = Request::from([ Request::OPTION_IS_TRACE => false ]);
        $this->assertObjectNotHasAttribute('is_trace', $request);
        $this->assertEquals(RequestMethod::METHOD_GET, $request->method);
        $this->assertFalse($request->method->is_trace());
    }

    public function test_from_with_is_xhr()
    {
        $request = Request::from([ Request::OPTION_IS_XHR => true ]);
        $this->assertObjectNotHasAttribute('is_xhr', $request);
        $this->assertTrue($request->is_xhr);

        $request = Request::from([ Request::OPTION_IS_XHR => false ]);
        $this->assertObjectNotHasAttribute('is_xhr', $request);
        $this->assertFalse($request->is_xhr);
    }

    public function test_from_with_method()
    {
        $request = Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_OPTIONS ]);
        $this->assertObjectNotHasAttribute('method', $request);
        $this->assertEquals(RequestMethod::METHOD_OPTIONS, $request->method);
    }

    public function test_from_with_emulated_method()
    {
        $request = Request::from([

            Request::OPTION_METHOD => RequestMethod::METHOD_POST,
            Request::OPTION_REQUEST_PARAMS => [ '_method' => RequestMethod::METHOD_DELETE ]

        ]);

        $this->assertEquals(RequestMethod::METHOD_DELETE, $request->method);
    }

    public function test_from_with_path()
    {
        $request = Request::from([ Request::OPTION_PATH => '/path/' ]);
        $this->assertObjectNotHasAttribute('path', $request);
        $this->assertEquals('/path/', $request->path);

        $request = Request::from('/path/');
        $this->assertObjectNotHasAttribute('path', $request);
        $this->assertEquals('/path/', $request->path);
    }

    public function test_from_with_parent()
    {
        $this->expectException(InvalidArgumentException::class);

        Request::from([ 'parent' => true ]);
    }

    public function test_from_with_query_string()
    {
        $this->expectException(InvalidArgumentException::class);

        Request::from([ 'query_string' => true ]);
    }

    public function test_from_with_referer()
    {
        $value = 'http://example.org/referer/';
        $request = Request::from([ Request::OPTION_REFERER => $value ]);
        $this->assertObjectNotHasAttribute('referer', $request);
        $this->assertEquals($value, $request->referer);
    }

    public function test_from_with_script_name()
    {
        $this->expectException(InvalidArgumentException::class);

        Request::from([ 'script_name' => true ]);
    }

    public function test_from_with_uri()
    {
        $value = '/uri/';
        $request = Request::from([ Request::OPTION_URI => $value ]);
        $this->assertObjectNotHasAttribute('uri', $request);
        $this->assertEquals($value, $request->uri);

        $request = Request::from($value);
        $this->assertObjectNotHasAttribute('uri', $request);
        $this->assertEquals($value, $request->uri);
    }

    public function test_from_with_uri_and_query_string()
    {
        $param1 = 1;
        $param2 = "\\a";
        $param3 = "L'été est là";

        $path = '/uri/';
        $query_string = http_build_query([ 'p1' => $param1, 'p2' => $param2, 'p3' => $param3 ]);
        $uri = "{$path}?{$query_string}";
        $request = Request::from($uri);
        $this->assertObjectNotHasAttribute('uri', $request);
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

    public function test_from_with_user_agent()
    {
        $request = Request::from([ Request::OPTION_USER_AGENT => 'Madonna' ]);
        $this->assertObjectNotHasAttribute('user_agent', $request);
        $this->assertEquals('Madonna', $request->user_agent);
    }

    public function test_from_with_files()
    {
        $request = Request::from('/path/to/file');
        $this->assertInstanceOf(FileList::class, $request->files);
        $this->assertEquals(0, $request->files->count());

        $request = Request::from([

            Request::OPTION_FILES => [

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

    public function test_from_with_headers()
    {
        $request = Request::from([

            Request::OPTION_URI => '/path/to/file',
            Request::OPTION_HEADERS => [

                "Cache-Control" => "max-age=0",
                "Accept" => "application/json"

            ]

        ]);

        $this->assertInstanceOf(Headers::class, $request->headers);
        $this->assertEquals("max-age=0", (string) $request->headers['Cache-Control']);
        $this->assertEquals("application/json", (string) $request->headers['Accept']);

        $headers = new Headers([

            "Cache-Control" => "max-age=0",
            "Accept" => "application/json"

        ]);

        $request = Request::from([

            Request::OPTION_URI => '/path/to/file',
            Request::OPTION_HEADERS => $headers

        ]);

        $this->assertSame($headers, $request->headers);
    }

    /**
     * @dataProvider provide_test_get_is_local
     *
     * @param string $ip
     * @param bool $expected
     */
    public function test_get_is_local($ip, $expected)
    {
        $request = Request::from([ Request::OPTION_IP => $ip ]);
        $this->assertEquals($expected, $request->is_local);
    }

    public function provide_test_get_is_local()
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

    public function test_get_script_name()
    {
        $expected = __FILE__;
        $request = Request::from([], [ 'SCRIPT_NAME' => $expected ]);
        $this->assertEquals($expected, $request->script_name);
    }

    /**
     * @dataProvider provide_test_get_authorization
     *
     * @param array $env
     * @param string $expected
     */
    public function test_get_authorization($env, $expected)
    {
        $request = Request::from([], $env);
        $this->assertEquals($expected, $request->authorization);
    }

    public function provide_test_get_authorization()
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

    public function test_get_port()
    {
        $expected = '1234';
        $request = Request::from([], [ 'REQUEST_PORT' => $expected ]);
        $this->assertEquals($expected, $request->port);
    }

    public function test_get_normalized_path()
    {
        $expected = '/';
        $request = Request::from('/index.php');
        $this->assertEquals($expected, $request->normalized_path);
    }

    public function test_get_extension()
    {
        $request = Request::from('/cat.gif');
        $this->assertEquals('gif', $request->extension);
    }

    public function test_query_string_from_uri()
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

    public function test_path_when_uri_is_missing_query_string()
    {
        $request = Request::from([], [ 'QUERY_STRING' => 'redirect_to=haven', 'REQUEST_URI' => '/api/users/login' ]);
        $this->assertEquals('redirect_to=haven', $request->query_string);
        $this->assertEquals('/api/users/login', $request->uri);
        $this->assertEquals('/api/users/login', $request->path);
    }

    public function test_params()
    {
        $request = Request::from([

            Request::OPTION_PATH_PARAMS => [

                'p1' => 1,
                'p2' => 2

            ],

            Request::OPTION_REQUEST_PARAMS => [

                'p1' => 10,
                'p2' => 20,
                'p3' => 3

            ],

            Request::OPTION_QUERY_PARAMS => [

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
        unset($request->params);
        $expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4, 'p5' => 5 ];
        $this->assertEquals($expected, $request->params);

        unset($request->params['p5']);
        $expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ];
        $this->assertEquals($expected, $request->params);
    }

    /**
     * @dataProvider provide_test_change
     *
     * @param array $properties
     */
    public function test_change(array $properties)
    {
        $this->markTestSkipped("Request::OPTION_IS_* need to be removed");

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

    public function provide_test_change()
    {
        return [

            [ [ Request::OPTION_IS_GET => true ] ],
            [ [ Request::OPTION_IS_HEAD => true ] ],
            [ [ Request::OPTION_IS_POST => true ] ],
            [ [ Request::OPTION_IS_PUT => true ] ],
            [ [ Request::OPTION_IS_DELETE => true ] ],
            [ [ Request::OPTION_IS_POST => true, Request::OPTION_IS_XHR => true ] ],
            [ [ Request::OPTION_IS_POST => true, Request::OPTION_IS_XHR => false ] ],
            [ [ Request::OPTION_METHOD => RequestMethod::METHOD_CONNECT ] ],
            [ [ Request::OPTION_URI => '/path/to/something' ] ],
            [ [ Request::OPTION_URI => '/path/to/something-else' ] ],

        ];
    }

    public function test_change_with_previous_params()
    {
        $request1 = Request::from([

            Request::OPTION_REQUEST_PARAMS => [ 'rp1' => 'one', 'rp2' => 'two' ],
            Request::OPTION_QUERY_PARAMS => [ 'qp1' => 'one' ],
            Request::OPTION_PATH_PARAMS => [ 'pp1' => 'one' ]

        ]);

        $this->assertSame([ 'pp1' => 'one', 'rp1' => 'one', 'rp2' => 'two', 'qp1' => 'one' ], $request1->params);

        $request2 = $request1->with([

            Request::OPTION_REQUEST_PARAMS => [],
            Request::OPTION_PATH_PARAMS => [ 'pp2' => 'two' ]

        ]);

        $this->assertSame([ ], $request2->request_params);
        $this->assertSame([ 'pp2' => 'two' ], $request2->path_params);
        $this->assertSame([ 'pp2' => 'two', 'qp1' => 'one' ], $request2->params);

        $request3 = $request2->with([

            Request::OPTION_QUERY_PARAMS => [],
            Request::OPTION_PATH_PARAMS => []

        ]);

        $this->assertSame([ ], $request3->request_params);
        $this->assertSame([ ], $request3->query_params);
        $this->assertSame([ ], $request3->path_params);
        $this->assertSame([ ], $request3->params);
    }

    public function test_should_throw_exception_when_changing_with_unsupported_property()
    {
        $request = Request::from();

        $this->expectException(InvalidArgumentException::class);

        $request->with([ 'unsupported_property' => uniqid() ]);
    }

    public function test_send()
    {
        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request_params = [

            'p1' => uniqid(),
            'p2' => uniqid()

        ];

        $properties = [

            Request::OPTION_METHOD => RequestMethod::METHOD_POST,
            Request::OPTION_REQUEST_PARAMS => $request_params,
            Request::OPTION_PATH_PARAMS => [],
            Request::OPTION_QUERY_PARAMS => []

        ];

        $request1 = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'with' ])
            ->getMock();

        $request2 = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'dispatch' ])
            ->getMock();

        $request2->expects($this->once())
            ->method('dispatch')
            ->willReturn($response);

        $request1->expects($this->once())
            ->method('with')
            ->with($properties)
            ->willReturn($request2);

        /* @var $request1 Request */

        $this->assertSame($response, $request1->post($request_params));
    }

    public function test_invoke()
    {
        $response = new Response();

        $request = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'dispatch' ])
            ->getMock();

        $request->expects($this->once())
            ->method('dispatch')
            ->willReturn($response);

        /* @var $request Request */

        $this->assertSame($response, $request());
    }

    public function test_invoke_with_exception()
    {
        $exception = new \Exception();

        $request = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'dispatch' ])
            ->getMock();

        $request->expects($this->once())
            ->method('dispatch')
            ->willThrowException($exception);

        /* @var $request Request */

        try {
            $request();

            $this->fail("Expected exception.");
        } catch (\Exception $e) {
            $this->assertNull(Request::get_current_request());
            $this->assertSame($exception, $e);
        }
    }

    public function test_parent()
    {
        $response = new Response();

        $request1 = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'dispatch' ])
            ->getMock();

        $request2 = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'dispatch' ])
            ->getMock();

        $request1->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function () use ($request2) {

                /* @var $request2 Request */

                return $request2();
            });

        $request2->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function () use ($response, $request1, $request2) {

                /* @var $request2 Request */

                $this->assertEquals($request2->parent, $request1);

                return $response;
            });

        /* @var $request1 Request */

        $this->assertSame($response, $request1());
    }
}
