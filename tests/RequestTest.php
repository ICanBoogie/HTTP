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

class RequestTest extends \PHPUnit_Framework_TestCase
{
	static private $request;

	static public function setupBeforeClass()
	{
		self::$request = Request::from($_SERVER);
	}

	public function test_clone()
	{
		$r1 = Request::from($_SERVER);
		$r2 = clone $r1;

		$this->assertNotSame($r1->headers, $r2->headers);
		$this->assertNotSame($r1->context, $r2->context);
	}

	/**
	 * @dataProvider provide_test_write_readonly_properties
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 *
	 * @param string $property Property name.
	 */
	public function test_write_readonly_properties($property)
	{
		self::$request->$property = null;
	}

	public function provide_test_write_readonly_properties()
	{
		$properties = 'authorization content_length cache_control context extension ip'
		. ' is_delete is_get is_head is_local is_options is_patch is_post is_put is_trace is_xhr'
		. ' normalized_path method path port parent query_string referer script_name uri'
		. ' user_agent files';

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	public function test_from_with_cache_control()
	{
		$v = "public, must-revalidate";
		$r = Request::from([ Request::OPTION_CACHE_CONTROL => $v ]);
		$this->assertObjectNotHasAttribute('cache_control', $r);
		$this->assertInstanceOf(Headers\CacheControl::class, $r->cache_control);
		$this->assertEquals('public', $r->cache_control->cacheable);
		$this->assertTrue($r->cache_control->must_revalidate);
	}

	public function test_from_with_content_length()
	{
		$v = 123456789;
		$r = Request::from([ Request::OPTION_CONTENT_LENGTH => $v ]);
		$this->assertObjectNotHasAttribute('content_length', $r);
		$this->assertEquals($v, $r->content_length);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_extension()
	{
		Request::from([ 'extension' => '.png' ]);
	}

	public function test_from_with_ip()
	{
		$v = '192.168.13.69';
		$r = Request::from([ Request::OPTION_IP => $v ]);
		$this->assertObjectNotHasAttribute('ip', $r);
		$this->assertEquals($v, $r->ip);
	}

	public function test_from_with_forwarded_ip()
	{
		$v = '192.168.13.69';
		$r = Request::from([ Request::OPTION_HEADERS => [

			'X-Forwarded-For' => "$v,::1"

		] ]);

		$this->assertEquals($v, $r->ip);
	}

	public function test_from_with_is_delete()
	{
		$r = Request::from([ Request::OPTION_IS_DELETE => true ]);
		$this->assertObjectNotHasAttribute('is_delete', $r);
		$this->assertEquals(Request::METHOD_DELETE, $r->method);
		$this->assertTrue($r->is_delete);

		$r = Request::from([ Request::OPTION_IS_DELETE => false ]);
		$this->assertObjectNotHasAttribute('is_delete', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_delete);
	}

	public function test_from_with_is_get()
	{
		$r = Request::from([ Request::OPTION_IS_GET => true ]);
		$this->assertObjectNotHasAttribute('is_get', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertTrue($r->is_get);

		$r = Request::from([ Request::OPTION_IS_GET => false ]);
		$this->assertObjectNotHasAttribute('is_get', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertTrue($r->is_get);
	}

	public function test_from_with_is_head()
	{
		$r = Request::from([ Request::OPTION_IS_HEAD => true ]);
		$this->assertObjectNotHasAttribute('is_head', $r);
		$this->assertEquals(Request::METHOD_HEAD, $r->method);
		$this->assertTrue($r->is_head);

		$r = Request::from([ Request::OPTION_IS_HEAD => false ]);
		$this->assertObjectNotHasAttribute('is_head', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_head);
	}

	public function test_from_with_is_local()
	{
		$r = Request::from([ Request::OPTION_IS_LOCAL => true ]);
		$this->assertObjectNotHasAttribute('is_local', $r);
		$this->assertTrue($r->is_local);

		$r = Request::from([ Request::OPTION_IS_LOCAL => false ]);
		$this->assertObjectNotHasAttribute('is_local', $r);
		$this->assertTrue($r->is_local); // yes is_local is `true` even if it was defined as `false`, that's because IP is not defined.
	}

	public function test_from_with_is_options()
	{
		$r = Request::from([ Request::OPTION_IS_OPTIONS => true ]);
		$this->assertObjectNotHasAttribute('is_options', $r);
		$this->assertEquals(Request::METHOD_OPTIONS, $r->method);
		$this->assertTrue($r->is_options);

		$r = Request::from([ Request::OPTION_IS_OPTIONS => false ]);
		$this->assertObjectNotHasAttribute('is_options', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_options);
	}

	public function test_from_with_is_patch()
	{
		$r = Request::from([ Request::OPTION_IS_PATCH => true ]);
		$this->assertObjectNotHasAttribute('is_patch', $r);
		$this->assertEquals(Request::METHOD_PATCH, $r->method);
		$this->assertTrue($r->is_patch);

		$r = Request::from([ Request::OPTION_IS_PATCH => false ]);
		$this->assertObjectNotHasAttribute('is_patch', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_patch);
	}

	public function test_from_with_is_post()
	{
		$r = Request::from([ Request::OPTION_IS_POST => true ]);
		$this->assertObjectNotHasAttribute('is_post', $r);
		$this->assertEquals(Request::METHOD_POST, $r->method);
		$this->assertTrue($r->is_post);

		$r = Request::from([ Request::OPTION_IS_POST => false ]);
		$this->assertObjectNotHasAttribute('is_post', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_post);
	}

	public function test_from_with_is_put()
	{
		$r = Request::from([ Request::OPTION_IS_PUT => true ]);
		$this->assertObjectNotHasAttribute('is_put', $r);
		$this->assertEquals(Request::METHOD_PUT, $r->method);
		$this->assertTrue($r->is_put);

		$r = Request::from([ Request::OPTION_IS_PUT => false ]);
		$this->assertObjectNotHasAttribute('is_put', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_put);
	}

	public function test_from_with_is_trace()
	{
		$r = Request::from([ Request::OPTION_IS_TRACE => true ]);
		$this->assertObjectNotHasAttribute('is_trace', $r);
		$this->assertEquals(Request::METHOD_TRACE, $r->method);
		$this->assertTrue($r->is_trace);

		$r = Request::from([ Request::OPTION_IS_TRACE => false ]);
		$this->assertObjectNotHasAttribute('is_trace', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_trace);
	}

	public function test_from_with_is_xhr()
	{
		$r = Request::from([ Request::OPTION_IS_XHR => true ]);
		$this->assertObjectNotHasAttribute('is_xhr', $r);
		$this->assertTrue($r->is_xhr);

		$r = Request::from([ Request::OPTION_IS_XHR => false ]);
		$this->assertObjectNotHasAttribute('is_xhr', $r);
		$this->assertFalse($r->is_xhr);
	}

	public function test_from_with_method()
	{
		$r = Request::from([ Request::OPTION_METHOD => Request::METHOD_OPTIONS ]);
		$this->assertObjectNotHasAttribute('method', $r);
		$this->assertEquals(Request::METHOD_OPTIONS, $r->method);
	}

	public function test_from_with_emulated_method()
	{
		$r = Request::from([

			Request::OPTION_METHOD => Request::METHOD_POST,
			Request::OPTION_REQUEST_PARAMS => [ '_method' => Request::METHOD_DELETE ]

		]);

		$this->assertEquals(Request::METHOD_DELETE, $r->method);
	}

	/**
	 * @expectedException \ICanBoogie\HTTP\MethodNotSupported
	 */
	public function test_from_with_invalid_method()
	{
		Request::from([ Request::OPTION_METHOD => uniqid() ]);
	}

	public function test_from_with_path()
	{
		$r = Request::from([ Request::OPTION_PATH => '/path/' ]);
		$this->assertObjectNotHasAttribute('path', $r);
		$this->assertEquals('/path/', $r->path);

		$r = Request::from('/path/');
		$this->assertObjectNotHasAttribute('path', $r);
		$this->assertEquals('/path/', $r->path);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_parent()
	{
		Request::from([ 'parent' => true ]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_query_string()
	{
		Request::from([ 'query_string' => true ]);
	}

	public function test_from_with_referer()
	{
		$v = 'http://example.org/referer/';
		$r = Request::from([ Request::OPTION_REFERER => $v ]);
		$this->assertObjectNotHasAttribute('referer', $r);
		$this->assertEquals($v, $r->referer);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_script_name()
	{
		Request::from([ 'script_name' => true ]);
	}

	public function test_from_with_uri()
	{
		$v = '/uri/';
		$r = Request::from([ Request::OPTION_URI => $v ]);
		$this->assertObjectNotHasAttribute('uri', $r);
		$this->assertEquals($v, $r->uri);

		$r = Request::from($v);
		$this->assertObjectNotHasAttribute('uri', $r);
		$this->assertEquals($v, $r->uri);
	}

	public function test_from_with_uri_and_query_string()
	{
		$p1 = 1;
		$p2 = "\\a";
		$p3 = "L'été est là";

		$path = '/uri/';
		$query_string = http_build_query([ 'p1' => $p1, 'p2' => $p2, 'p3' => $p3 ]);
		$v = "{$path}?{$query_string}";
		$r = Request::from($v);
		$this->assertObjectNotHasAttribute('uri', $r);
		$this->assertEquals($v, $r->uri);
		$this->assertEquals($path, $r->path);
		$this->assertEquals($query_string, $r->query_string);
		$this->assertArrayHasKey('p1', $r->query_params);
		$this->assertArrayHasKey('p2', $r->query_params);
		$this->assertArrayHasKey('p3', $r->query_params);
		$this->assertArrayHasKey('p1', $r->params);
		$this->assertArrayHasKey('p2', $r->params);
		$this->assertArrayHasKey('p3', $r->params);
		$this->assertEquals($p1, $r->query_params['p1']);
		$this->assertEquals($p2, $r->query_params['p2']);
		$this->assertEquals($p3, $r->query_params['p3']);
		$this->assertEquals($p1, $r['p1']);
		$this->assertEquals($p2, $r['p2']);
		$this->assertEquals($p3, $r['p3']);
	}

	public function test_from_with_user_agent()
	{
		$r = Request::from([ Request::OPTION_USER_AGENT => 'Madonna' ]);
		$this->assertObjectNotHasAttribute('user_agent', $r);
		$this->assertEquals('Madonna', $r->user_agent);
	}

	public function test_from_with_files()
	{
		$r = Request::from('/path/to/file');
		$this->assertInstanceOf(FileList::class, $r->files);
		$this->assertEquals(0, $r->files->count());

		$r = Request::from([

			Request::OPTION_FILES => [

				'one' => [ 'pathname' => __FILE__ ],
				'two' => [ 'pathname' => __FILE__ ]

			]

		]);

		$this->assertInstanceOf(FileList::class, $r->files);
		$this->assertEquals(2, $r->files->count());

		foreach ([ 'one', 'two' ] as $id)
		{
			$this->assertInstanceOf(File::class, $r->files[$id]);
			$this->assertEquals(__FILE__, $r->files[$id]->pathname);
		}
	}

	public function test_from_with_headers()
	{
		$r = Request::from([

			Request::OPTION_URI => '/path/to/file',
			Request::OPTION_HEADERS => [

				"Cache-Control" => "max-age=0",
				"Accept" => "application/json"

			]

		]);

		$this->assertInstanceOf(Headers::class, $r->headers);
		$this->assertEquals("max-age=0", (string) $r->headers['Cache-Control']);
		$this->assertEquals("application/json", (string) $r->headers['Accept']);

		$headers = new Headers([

			"Cache-Control" => "max-age=0",
			"Accept" => "application/json"

		]);

		$r = Request::from([

			Request::OPTION_URI => '/path/to/file',
			Request::OPTION_HEADERS => $headers

		]);

		$this->assertSame($headers, $r->headers);
	}

	/**
	 * @dataProvider provide_test_get_is_safe
	 *
	 * @param string $method
	 * @param bool $expected
	 */
	public function test_get_is_safe($method, $expected)
	{
		$this->assertSame($expected, Request::from([ Request::OPTION_METHOD => $method ])->is_safe);
	}

	public function provide_test_get_is_safe()
	{
		return [

			[ Request::METHOD_CONNECT, true ],
			[ Request::METHOD_DELETE,  false ],
			[ Request::METHOD_GET,     true ],
			[ Request::METHOD_HEAD,    true ],
			[ Request::METHOD_OPTIONS, true ],
			[ Request::METHOD_PATCH,   false ],
			[ Request::METHOD_POST,    false ],
			[ Request::METHOD_PUT,     false ],
			[ Request::METHOD_TRACE,   true ],

		];
	}

	/**
	 * @dataProvider provide_test_get_is_idempotent
	 *
	 * @param string $method
	 * @param bool $expected
	 */
	public function test_get_is_idempotent($method, $expected)
	{
		$this->assertSame($expected, Request::from([ Request::OPTION_METHOD => $method ])->is_idempotent);
	}

	public function provide_test_get_is_idempotent()
	{
		return [

			[ Request::METHOD_CONNECT, true ],
			[ Request::METHOD_DELETE,  true ],
			[ Request::METHOD_GET,     true ],
			[ Request::METHOD_HEAD,    true ],
			[ Request::METHOD_OPTIONS, true ],
			[ Request::METHOD_PATCH,   false ],
			[ Request::METHOD_POST,    false ],
			[ Request::METHOD_PUT,     true ],
			[ Request::METHOD_TRACE,   true ],

		];
	}

	/**
	 * @dataProvider provide_test_get_is_local
	 *
	 * @param string $ip
	 * @param bool $expected
	 */
	public function test_get_is_local($ip, $expected)
	{
		$r = Request::from([ Request::OPTION_IP => $ip ]);
		$this->assertEquals($expected, $r->is_local);
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
		$r = Request::from([], [ 'SCRIPT_NAME' => $expected ]);
		$this->assertEquals($expected, $r->script_name);
	}

	/**
	 * @dataProvider provide_test_get_authorization
	 *
	 * @param array $env
	 * @param string $expected
	 */
	public function test_get_authorization($env, $expected)
	{
		$r = Request::from([], $env);
		$this->assertEquals($expected, $r->authorization);
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
		$r = Request::from([], [ 'REQUEST_PORT' => $expected ]);
		$this->assertEquals($expected, $r->port);
	}

	public function test_get_normalized_path()
	{
		$expected = '/';
		$r = Request::from('/index.php');
		$this->assertEquals($expected, $r->normalized_path);
	}

	public function test_get_extension()
	{
		$r = Request::from('/cat.gif');
		$this->assertEquals('gif', $r->extension);
	}

	public function test_query_string_from_uri()
	{
		$p = '/api/users/login';
		$q = 'redirect_to=haven';
		$r = Request::from($p, [ 'QUERY_STRING' => $q ]);
		$this->assertEmpty($r->query_string);
		$this->assertEquals($p, $r->uri);
		$this->assertEquals($p, $r->path);

		$r = Request::from($p . '?' . $q);
		$this->assertEquals($q, $r->query_string);
		$this->assertEquals($p . '?' . $q, $r->uri);
		$this->assertEquals($p, $r->path);
		$this->assertArrayHasKey('redirect_to', $r->query_params);
		$this->assertArrayHasKey('redirect_to', $r->params);
		$this->assertEquals('haven', $r->query_params['redirect_to']);
		$this->assertEquals('haven', $r->params['redirect_to']);
		$this->assertEquals('haven', $r['redirect_to']);
	}

	public function test_path_when_uri_is_missing_query_string()
	{
		$r = Request::from([], [ 'QUERY_STRING' => 'redirect_to=haven', 'REQUEST_URI' => '/api/users/login' ]);
		$this->assertEquals('redirect_to=haven', $r->query_string);
		$this->assertEquals('/api/users/login', $r->uri);
		$this->assertEquals('/api/users/login', $r->path);
	}

	public function test_params()
	{
		$r = Request::from([

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

		$this->assertSame([ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ], $r->params);

		$r['p5'] = 5;
		$this->assertTrue(isset($r['p5']));

		$expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4, 'p5' => 5 ];
		$this->assertSame($expected, $r->params);
		unset($r->params);
		$expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4, 'p5' => 5 ];
		$this->assertEquals($expected, $r->params);

		unset($r['p5']);
		$expected = [ 'p1' => 1, 'p2' => 2, 'p3' => 3, 'p4' => 4 ];
		$this->assertEquals($expected, $r->params);
		$this->assertFalse(isset($r['p5']));

		$t = [];

		foreach ($r as $k => $v)
		{
			$t[$k] = $v;
		}

		$this->assertEquals($expected, $t);
	}

	/**
	 * @dataProvider provide_test_change
	 *
	 * @param array $properties
	 */
	public function test_change(array $properties)
	{
		static $iterated;

		if (!$iterated)
		{
			$iterated = Request::from();
		}

		$changed = $iterated->with($properties);

		$this->assertNotSame($changed, $iterated);

		foreach ($properties as $property => $value)
		{
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
			[ [ Request::OPTION_METHOD => Request::METHOD_CONNECT ] ],
			[ [ Request::OPTION_URI => '/path/to/something' ] ],
			[ [ Request::OPTION_URI => '/path/to/something-else' ] ],

		];
	}

	public function test_change_with_previous_params()
	{
		$r1 = Request::from([

			Request::OPTION_REQUEST_PARAMS => [ 'rp1' => 'one', 'rp2' => 'two' ],
			Request::OPTION_QUERY_PARAMS => [ 'qp1' => 'one' ],
			Request::OPTION_PATH_PARAMS => [ 'pp1' => 'one' ]

		]);

		$this->assertSame([ 'pp1' => 'one', 'rp1' => 'one', 'rp2' => 'two', 'qp1' => 'one' ], $r1->params);

		$r2 = $r1->with([

			Request::OPTION_REQUEST_PARAMS => [],
			Request::OPTION_PATH_PARAMS => [ 'pp2' => 'two' ]

		]);

		$this->assertSame([ ], $r2->request_params);
		$this->assertSame([ 'pp2' => 'two' ], $r2->path_params);
		$this->assertSame([ 'pp2' => 'two', 'qp1' => 'one' ], $r2->params);

		$r3 = $r2->with([

			Request::OPTION_QUERY_PARAMS => [],
			Request::OPTION_PATH_PARAMS => []

		]);

		$this->assertSame([ ], $r3->request_params);
		$this->assertSame([ ], $r3->query_params);
		$this->assertSame([ ], $r3->path_params);
		$this->assertSame([ ], $r3->params);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_should_throw_exception_when_changing_with_unsupported_property()
	{
		$r = Request::from();
		$r->with([ 'unsupported_property' => uniqid() ]);
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

			Request::OPTION_METHOD => Request::METHOD_POST,
			Request::OPTION_REQUEST_PARAMS => $request_params,
			Request::OPTION_PATH_PARAMS => [],
			Request::OPTION_QUERY_PARAMS => []

		];

		$r1 = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'with' ])
			->getMock();

		$r2 = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'dispatch' ])
			->getMock();

		$r2->expects($this->once())
			->method('dispatch')
			->willReturn($response);

		$r1->expects($this->once())
			->method('with')
			->with($properties)
			->willReturn($r2);

		/* @var $r1 Request */

		$this->assertSame($response, $r1->post($request_params));
	}

	/**
	 * @expectedException \ICanBoogie\Prototype\MethodNotDefined
	 */
	public function test_should_throw_exception_when_invoking_undefined_method()
	{
		$r = Request::from();
		$m = 'undefined' . uniqid();
		$r->$m();
	}

	public function test_invoke()
	{
		$response = new Response;

		$r = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'dispatch' ])
			->getMock();

		$r->expects($this->once())
			->method('dispatch')
			->willReturn($response);

		/* @var $r Request */

		$this->assertSame($response, $r());
	}

	public function test_invoke_with_exception()
	{
		$exception = new \Exception;

		$r = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'dispatch' ])
			->getMock();

		$r->expects($this->once())
			->method('dispatch')
			->willThrowException($exception);

		/* @var $r Request */

		try
		{
			$r();

			$this->fail("Expected exception.");
		}
		catch (\Exception $e)
		{
			$this->assertNull(Request::get_current_request());
			$this->assertSame($exception, $e);
		}
	}

	public function test_parent()
	{
		$response = new Response;

		$r1 = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'dispatch' ])
			->getMock();

		$r2 = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->setMethods([ 'dispatch' ])
			->getMock();

		$r1->expects($this->once())
			->method('dispatch')
			->willReturnCallback(function() use ($r2) {

				/* @var $r2 Request */

				return $r2();

			});

		$r2->expects($this->once())
			->method('dispatch')
			->willReturnCallback(function() use ($response, $r1, $r2) {

				/* @var $r2 Request */

				$this->assertEquals($r2->parent, $r1);

				return $response;

			});

		/* @var $r1 Request */

		$this->assertSame($response, $r1());
	}
}
