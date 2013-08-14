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

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_authorization()
	{
		self::$request->authorization = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_cache_control()
	{
		self::$request->cache_control = true;
	}

	public function test_from_with_cache_control()
	{
		$v = "public, must-revalidate";
		$r = Request::from(array('cache_control' => $v));
		$this->assertObjectNotHasAttribute('cache_control', $r);
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\CacheControl', $r->cache_control);
		$this->assertEquals('public', $r->cache_control->cacheable);
		$this->assertTrue($r->cache_control->must_revalidate);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_content_length()
	{
		self::$request->content_length = true;
	}

	public function test_from_with_content_length()
	{
		$v = 123456789;
		$r = Request::from(array('content_length' => $v));
		$this->assertObjectNotHasAttribute('content_length', $r);
		$this->assertEquals($v, $r->content_length);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_context()
	{
		self::$request->context = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_extension()
	{
		self::$request->extension = true;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_extension()
	{
		Request::from(array('extension' => '.png'));
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_ip()
	{
		self::$request->ip = true;
	}

	public function test_from_with_ip()
	{
		$v = '192.168.13.69';
		$r = Request::from(array('ip' => $v));
		$this->assertObjectNotHasAttribute('ip', $r);
		$this->assertEquals($v, $r->ip);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_delete()
	{
		self::$request->is_delete = true;
	}

	public function test_from_with_is_delete()
	{
		$r = Request::from(array('is_delete' => true));
		$this->assertObjectNotHasAttribute('is_delete', $r);
		$this->assertEquals(Request::METHOD_DELETE, $r->method);
		$this->assertTrue($r->is_delete);

		$r = Request::from(array('is_delete' => false));
		$this->assertObjectNotHasAttribute('is_delete', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_delete);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_get()
	{
		self::$request->is_get = true;
	}

	public function test_from_with_is_get()
	{
		$r = Request::from(array('is_get' => true));
		$this->assertObjectNotHasAttribute('is_get', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertTrue($r->is_get);

		$r = Request::from(array('is_get' => false));
		$this->assertObjectNotHasAttribute('is_get', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertTrue($r->is_get);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_head()
	{
		self::$request->is_head = true;
	}

	public function test_from_with_is_head()
	{
		$r = Request::from(array('is_head' => true));
		$this->assertObjectNotHasAttribute('is_head', $r);
		$this->assertEquals(Request::METHOD_HEAD, $r->method);
		$this->assertTrue($r->is_head);

		$r = Request::from(array('is_head' => false));
		$this->assertObjectNotHasAttribute('is_head', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_head);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_local()
	{
		self::$request->is_local = true;
	}

	public function test_from_with_is_local()
	{
		$r = Request::from(array('is_local' => true));
		$this->assertObjectNotHasAttribute('is_local', $r);
		$this->assertTrue($r->is_local);

		$r = Request::from(array('is_local' => false));
		$this->assertObjectNotHasAttribute('is_local', $r);
		$this->assertTrue($r->is_local); // yes is_local is `true` even if it was defined as `false`, that's because IP is not defined.
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_options()
	{
		self::$request->is_options = true;
	}

	public function test_from_with_is_options()
	{
		$r = Request::from(array('is_options' => true));
		$this->assertObjectNotHasAttribute('is_options', $r);
		$this->assertEquals(Request::METHOD_OPTIONS, $r->method);
		$this->assertTrue($r->is_options);

		$r = Request::from(array('is_options' => false));
		$this->assertObjectNotHasAttribute('is_options', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_options);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_patch()
	{
		self::$request->is_patch = true;
	}

	public function test_from_with_is_patch()
	{
		$r = Request::from(array('is_patch' => true));
		$this->assertObjectNotHasAttribute('is_patch', $r);
		$this->assertEquals(Request::METHOD_PATCH, $r->method);
		$this->assertTrue($r->is_patch);

		$r = Request::from(array('is_patch' => false));
		$this->assertObjectNotHasAttribute('is_patch', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_patch);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_post()
	{
		self::$request->is_post = true;
	}

	public function test_from_with_is_post()
	{
		$r = Request::from(array('is_post' => true));
		$this->assertObjectNotHasAttribute('is_post', $r);
		$this->assertEquals(Request::METHOD_POST, $r->method);
		$this->assertTrue($r->is_post);

		$r = Request::from(array('is_post' => false));
		$this->assertObjectNotHasAttribute('is_post', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_post);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_put()
	{
		self::$request->is_put = true;
	}

	public function test_from_with_is_put()
	{
		$r = Request::from(array('is_put' => true));
		$this->assertObjectNotHasAttribute('is_put', $r);
		$this->assertEquals(Request::METHOD_PUT, $r->method);
		$this->assertTrue($r->is_put);

		$r = Request::from(array('is_put' => false));
		$this->assertObjectNotHasAttribute('is_put', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_put);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_trace()
	{
		self::$request->is_trace = true;
	}

	public function test_from_with_is_trace()
	{
		$r = Request::from(array('is_trace' => true));
		$this->assertObjectNotHasAttribute('is_trace', $r);
		$this->assertEquals(Request::METHOD_TRACE, $r->method);
		$this->assertTrue($r->is_trace);

		$r = Request::from(array('is_trace' => false));
		$this->assertObjectNotHasAttribute('is_trace', $r);
		$this->assertEquals(Request::METHOD_GET, $r->method);
		$this->assertFalse($r->is_trace);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_is_xhr()
	{
		self::$request->is_xhr = true;
	}

	public function test_from_with_is_xhr()
	{
		$r = Request::from(array('is_xhr' => true));
		$this->assertObjectNotHasAttribute('is_xhr', $r);
		$this->assertTrue($r->is_xhr);

		$r = Request::from(array('is_xhr' => false));
		$this->assertObjectNotHasAttribute('is_xhr', $r);
		$this->assertFalse($r->is_xhr);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_normalized_path()
	{
		self::$request->normalized_path = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_method()
	{
		self::$request->method = true;
	}

	public function test_from_with_method()
	{
		$r = Request::from(array('method' => Request::METHOD_OPTIONS));
		$this->assertObjectNotHasAttribute('method', $r);
		$this->assertEquals(Request::METHOD_OPTIONS, $r->method);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_path()
	{
		self::$request->path = true;
	}

	public function test_from_with_path()
	{
		$r = Request::from(array('path' => '/path/'));
		$this->assertObjectNotHasAttribute('path', $r);
		$this->assertEquals('/path/', $r->path);

		$r = Request::from('/path/');
		$this->assertObjectNotHasAttribute('path', $r);
		$this->assertEquals('/path/', $r->path);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_port()
	{
		self::$request->port = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_previous()
	{
		self::$request->previous = true;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_previous()
	{
		Request::from(array('previous' => true));
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_query_string()
	{
		self::$request->query_string = true;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_query_string()
	{
		Request::from(array('query_string' => true));
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_referer()
	{
		self::$request->referer = true;
	}

	public function test_from_with_referer()
	{
		$v = 'http://example.org/referer/';
		$r = Request::from(array('referer' => $v));
		$this->assertObjectNotHasAttribute('referer', $r);
		$this->assertEquals($v, $r->referer);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_script_name()
	{
		self::$request->script_name = true;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_from_with_script_name()
	{
		Request::from(array('script_name' => true));
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_uri()
	{
		self::$request->uri = true;
	}

	public function test_from_with_uri()
	{
		$v = '/uri/';
		$r = Request::from(array('uri' => $v));
		$this->assertObjectNotHasAttribute('uri', $r);
		$this->assertEquals($v, $r->uri);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotWritable
	 */
	public function test_set_user_agent()
	{
		self::$request->user_agent = true;
	}

	public function test_from_with_user_agent()
	{
		$r = Request::from(array('user_agent' => 'Madonna'));
		$this->assertObjectNotHasAttribute('user_agent', $r);
		$this->assertEquals('Madonna', $r->user_agent);
	}

	public function test_query_string_from_uri()
	{
		$p = '/api/users/login';
		$q = 'redirect_to=haven';
		$r = Request::from($p, array(array('QUERY_STRING' => $q)));
		$this->assertEmpty($r->query_string);
		$this->assertEquals($p, $r->uri);
		$this->assertEquals($p, $r->path);

		$r = Request::from($p . '?' . $q);
		$this->assertEquals($q, $r->query_string);
		$this->assertEquals($p . '?' . $q, $r->uri);
		$this->assertEquals($p, $r->path);
	}

	public function test_path_when_uri_is_missing_query_string()
	{
		$r = Request::from(array(), array(array('QUERY_STRING' => 'redirect_to=haven', 'REQUEST_URI' => '/api/users/login')));
		$this->assertEquals('redirect_to=haven', $r->query_string);
		$this->assertEquals('/api/users/login', $r->uri);
		$this->assertEquals('/api/users/login', $r->path);
	}
}