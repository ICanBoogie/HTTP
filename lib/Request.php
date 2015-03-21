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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\Prototype\MethodNotDefined;

/**
 * An HTTP request.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Request;
 *
 * # Creating the main request
 *
 * $request = Request::from($_SERVER);
 *
 * # Creating a request from scratch, with the current environment.
 *
 * $request = Request::from([
 *
 *     'uri' => '/path/to/my/page.html?page=2',
 *     'user_agent' => 'Mozilla'
 *     'is_get' => true,
 *     'is_xhr' => true,
 *     'is_local' => true
 *
 * ], $_SERVER);
 * </pre>
 *
 * @method Response connect() connect(array $params=null)
 * @method Response delete() delete(array $params=null)
 * @method Response get() get(array $params=null)
 * @method Response head() head(array $params=null)
 * @method Response options() options(array $params=null)
 * @method Response post() post(array $params=null)
 * @method Response put() put(array $params=null)
 * @method Response patch() patch(array $params=null)
 * @method Response trace() trace(array $params=null)
 *
 * @property-read \ICanBoogie\HTTP\Request\Context $context the request's context.
 * @property-read Request $parent Parent request.
 * @property-read FileList $files The files associated with the request.
 *
 * @property-read boolean $authorization Authorization of the request.
 * @property-read int $content_length Length of the request content.
 * @property-read int $cache_control A {@link \ICanBoogie\HTTP\Headers\CacheControl} object.
 * @property-read string $ip Remote IP of the request.
 * @property-read boolean $is_delete Is this a `DELETE` request?
 * @property-read boolean $is_get Is this a `GET` request?
 * @property-read boolean $is_head Is this a `HEAD` request?
 * @property-read boolean $is_options Is this a `OPTIONS` request?
 * @property-read boolean $is_patch Is this a `PATCH` request?
 * @property-read boolean $is_post Is this a `POST` request?
 * @property-read boolean $is_put Is this a `PUT` request?
 * @property-read boolean $is_trace Is this a `TRACE` request?
 * @property-read boolean $is_local Is this a local request?
 * @property-read boolean $is_xhr Is this an Ajax request?
 * @property-read string $method Method of the request.
 * @property-read string $normalized_path Path of the request normalized using the {@link \ICanBoogie\normalize_url_path()} function.
 * @property-read string $path Path info of the request.
 * @property-read string $extension The extension of the path.
 * @property-read int $port Port of the request.
 * @property-read string $query_string Query string of the request.
 * @property-read string $script_name Name of the entered script.
 * @property-read string $referer Referer of the request.
 * @property-read string $user_agent User agent of the request.
 * @property-read string $uri URI of the request. The `QUERY_STRING` value of the environment
 * is overwritten when the instance is created with the {@link $uri} property.
 *
 * @see http://en.wikipedia.org/wiki/Uniform_resource_locator
 */
class Request implements \ArrayAccess, \IteratorAggregate
{
	use AccessorTrait;

	/*
	 * HTTP methods as defined by the {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html Hypertext Transfer protocol 1.1}.
	 */
	const METHOD_ANY = 'ANY';
	const METHOD_CONNECT = 'CONNECT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_TRACE = 'TRACE';

	static public $methods = [

		self::METHOD_CONNECT,
		self::METHOD_DELETE,
		self::METHOD_GET,
		self::METHOD_HEAD,
		self::METHOD_OPTIONS,
		self::METHOD_POST,
		self::METHOD_PUT,
		self::METHOD_PATCH,
		self::METHOD_TRACE

	];

	static private $properties_mappers;

	/**
	 * Returns request properties mappers.
	 *
	 * @return \Closure[]
	 */
	static protected function get_properties_mappers()
	{
		if (self::$properties_mappers)
		{
			return self::$properties_mappers;
		}

		return self::$properties_mappers = static::create_properties_mappers();
	}

	/**
	 * Returns request properties mappers.
	 *
	 * @return \Closure[]
	 */
	static protected function create_properties_mappers()
	{
		return [

			'path_params' =>    function($value) { return $value; },
			'query_params' =>   function($value) { return $value; },
			'request_params' => function($value) { return $value; },
			'cookie' =>         function($value) { return $value; },
			'files' =>          function($value) { return $value; },
			'headers' =>        function($value) { return ($value instanceof Headers) ? $value : new Headers($value); },

			'cache_control' =>  function($value, array &$env) { $env['HTTP_CACHE_CONTROL'] = $value; },
			'content_length' => function($value, array &$env) { $env['CONTENT_LENGTH'] = $value; },
			'ip' =>             function($value, array &$env) { if ($value) $env['REMOTE_ADDR'] = $value; },
			'is_local' =>       function($value, array &$env) { if ($value) $env['REMOTE_ADDR'] = '::1'; },
			'is_delete' =>      function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_DELETE; },
			'is_connect' =>     function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_CONNECT; },
			'is_get' =>         function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_GET; },
			'is_head' =>        function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_HEAD; },
			'is_options' =>     function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_OPTIONS; },
			'is_patch' =>       function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_PATCH; },
			'is_post' =>        function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_POST; },
			'is_put' =>         function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_PUT; },
			'is_trace' =>       function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = Request::METHOD_TRACE; },
			'is_xhr' =>         function($value, array &$env) { if ($value) $env['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; else unset($env['HTTP_X_REQUESTED_WITH']); },
			'method' =>         function($value, array &$env) { if ($value) $env['REQUEST_METHOD'] = $value; },
			'path' =>           function($value, array &$env) { $env['REQUEST_URI'] = $value; }, // TODO-20130521: handle query string
			'referer' =>        function($value, array &$env) { $env['HTTP_REFERER'] = $value; },
			'uri' =>            function($value, array &$env) { $env['REQUEST_URI'] = $value; $qs = strpos($value, '?'); $env['QUERY_STRING'] = $qs === false ? '' : substr($value, $qs + 1); },
			'user_agent' =>     function($value, array &$env) { $env['HTTP_USER_AGENT'] = $value; }

		];
	}

	/**
	 * Current request.
	 *
	 * @var Request
	 */
	static protected $current_request;

	/**
	 * Returns the current request.
	 *
	 * @return Request
	 */
	static public function get_current_request()
	{
		return self::$current_request;
	}

	/**
	 * Parameters extracted from the request path.
	 *
	 * @var array
	 */
	public $path_params = [];

	/**
	 * Parameters defined by the query string.
	 *
	 * @var array
	 */
	public $query_params = [];

	/**
	 * Parameters defined by the request body.
	 *
	 * @var array
	 */
	public $request_params = [];

	/**
	 * Union of {@link $path_params}, {@link $request_params} and {@link $query_params}.
	 *
	 * @var array
	 */
	public $params;

	/**
	 * General purpose container.
	 *
	 * @var Request\Context
	 */
	protected $context;

	/**
	 * The headers of the request.
	 *
	 * @var Headers
	 */
	public $headers;

	/**
	 * Request environment.
	 *
	 * @var array
	 */
	protected $env;

	/**
	 * Files associated with the request.
	 *
	 * @var FileList
	 */
	protected $files;

	protected function get_files()
	{
		if ($this->files instanceof FileList)
		{
			return $this->files;
		}

		return $this->files = FileList::from($this->files);
	}

	public $cookie;

	/**
	 * Parent request.
	 *
	 * @var Request
	 */
	protected $parent;

	/**
	 * A request can be created from the `$_SERVER` super global array. In that case `$_SERVER` is
	 * used as environment the request is created with the following properties:
	 *
	 * - {@link $cookie}: a reference to the `$_COOKIE` super global array.
	 * - {@link $path_params}: initialized to an empty array.
	 * - {@link $query_params}: a reference to the `$_GET` super global array.
	 * - {@link $request_params}: a reference to the `$_POST` super global array.
	 * - {@link $files}: a reference to the `$_FILES` super global array.
	 *
	 * A request can also be created from an array of properties, in which case most of them are
	 * mapped to the `$env` constructor param. For instance, `is_xhr` set the
	 * `HTTP_X_REQUESTED_WITH` environment property to 'XMLHttpRequest'. In fact, only the
	 * following parameters are preserved:
	 *
	 * - `path_params`
	 * - `query_params`
	 * - `request_params`
	 * - `files`: The files associated with the request.
	 * - `headers`: The header fields of the request. If specified, the headers available in the
	 * environment are ignored.
	 *
	 * @param array $properties Properties of the request.
	 * @param array $env Environment, usually the `$_SERVER` array.
	 *
	 * @throws \InvalidArgumentException in attempt to use a property that is not mapped to an
	 * environment property.
	 *
	 * @return Request
	 */
	static public function from($properties = null, array $env = [])
	{
		if (!$properties)
		{
			return new static([], $env);
		}

		if ($properties === $_SERVER)
		{
			return static::from_server();
		}

		if (is_string($properties) || (is_object($properties) && method_exists($properties, '__toString')))
		{
			return static::from_uri((string) $properties, $env);
		}

		return static::from_properties($properties, $env);
	}

	/**
	 * Creates an instance from the `$_SERVER` array.
	 *
	 * @return Request
	 */
	static protected function from_server()
	{
		return static::from([

			'cookie' => &$_COOKIE,
			'path_params' => [],
			'query_params' => &$_GET,
			'request_params' => &$_POST,
			'files' => &$_FILES // @codeCoverageIgnore

		], $_SERVER);
	}

	/**
	 * Creates an instance from an URI.
	 *
	 * @param string $uri
	 * @param array $env
	 *
	 * @return Request
	 */
	static protected function from_uri($uri, array $env)
	{
		return static::from([ 'uri' => $uri ], $env);
	}

	/**
	 * Creates an instance from an array of properties.
	 *
	 * @param array $properties
	 * @param array $env
	 *
	 * @return Request
	 */
	static protected function from_properties(array $properties, array $env)
	{
		$properties = $properties ?: [];

		$mappers = static::get_properties_mappers();

		foreach ($properties as $property => &$value)
		{
			if (empty($mappers[$property]))
			{
				throw new \InvalidArgumentException("Unsupported property: <q>$property</q>.");
			}

			$value = $mappers[$property]($value, $env);

			if ($value === null)
			{
				unset($properties[$property]);
			}
		}

		if (!empty($env['QUERY_STRING']))
		{
			parse_str($env['QUERY_STRING'], $properties['query_params']);
		}

		return new static($properties, $env);
	}

	/**
	 * Initialize the properties {@link $env}, {@link $headers} and {@link $context}.
	 *
	 * If the {@link $params} property is `null` it is set with an union of {@link $path_params},
	 * {@link $request_params} and {@link $query_params}.
	 *
	 * @param array $properties Initial properties.
	 * @param array $env Environment of the request, usually the `$_SERVER` super global.
	 *
	 * @throws MethodNotSupported when the request method is not supported.
	 */
	protected function __construct(array $properties, array $env = [])
	{
		$this->context = new Request\Context($this);
		$this->env = $env;

		foreach ($properties as $property => $value)
		{
			$this->$property = $value;
		}

		$this->assert_method($this->method);

		if (!$this->headers)
		{
			$this->headers = new Headers($env);
		}

		if ($this->params === null)
		{
			$this->params = $this->path_params + $this->request_params + $this->query_params;
		}
	}

	/**
	 * Clone {@link $headers} and {@link $context}, and unset {@link $params}.
	 */
	public function __clone()
	{
		$this->headers = clone $this->headers;
		$this->context = clone $this->context;
		unset($this->params);
	}

	/**
	 * Alias for {@link send()}.
	 *
	 * @return Response The response to the request.
	 */
	public function __invoke()
	{
		return $this->send();
	}

	/**
	 * Dispatch the request.
	 *
	 * The {@link parent} property is used for request chaining.
	 *
	 * Note: If an exception is thrown during dispatch {@link $current_request} is not updated!
	 *
	 * Note: If the request is changed because of the `$method` or `$params` parameters, it
	 * is the _changed_ instance that is dispatched, not the actual instance.
	 *
	 * @param string|null $method Use this parameter to change the request method.
	 * @param array|null $params Use this parameter to change the {@link $request_params}
	 * property of the request.
	 *
	 * @return Response The response to the request.
	 *
	 * @throws \Exception re-throws exception raised during dispatch.
	 */
	public function send($method = null, array $params = null)
	{
		$request = $this->adapt($method, $params);

		$this->parent = self::$current_request;

		self::$current_request = $request;

		try
		{
			$response = $request->dispatch();

			self::$current_request = $request->parent;

			return $response;
		}
		catch (\Exception $e)
		{
			self::$current_request = $request->parent;

			throw $e;
		}
	}

	/**
	 * Dispatches the request using the {@link dispatch()} helper.
	 *
	 * @return Response
	 */
	protected function dispatch()
	{
		return dispatch($this); // @codeCoverageIgnore
	}

	/**
	 * Asserts that a method is supported.
	 *
	 * @param string $method
	 *
	 * @throws MethodNotSupported
	 */
	private function assert_method($method)
	{
		if (!in_array($method, self::$methods))
		{
			throw new MethodNotSupported($method);
		}
	}

	/**
	 * Return a new instance with the specified changed properties.
	 *
	 * @param array $properties
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return Request
	 */
	public function change(array $properties)
	{
		$changed = clone $this;
		$mappers = static::get_properties_mappers();
		$env = &$changed->env;

		foreach ($properties as $property => $value)
		{
			if (empty($mappers[$property]))
			{
				throw new \InvalidArgumentException("Unsupported property: <q>$property</q>.");
			}

			$value = $mappers[$property]($value, $env);

			if ($value === null)
			{
				continue;
			}

			$changed->$property = $value;
		}

		return $changed;
	}

	/**
	 * Adapts the request to the specified method and params.
	 *
	 * @param string $method The method.
	 * @param array $params The params.
	 *
	 * @return Request The same instance is returned if the method is the same and the params
	 * are `null`. Otherwise a _changed_ request is returned.
	 */
	protected function adapt($method, array $params = null)
	{
		if ((!$method || $method == $this->method) && !$params)
		{
			return $this;
		}

		$properties = [];

		if ($method)
		{
			$properties = [ 'method' => $method ];
		}

		if ($params !== null)
		{
			$properties['request_params'] = $params;
			$properties['path_params'] = [];
			$properties['query_params'] = [];
		}

		return $this->change($properties);
	}

	/**
	 * Overrides the method to provide a virtual method for each request method.
	 *
	 * Example:
	 *
	 * <pre>
	 * <?php
	 *
	 * Request::from('/api/core/aloha')->get();
	 * </pre>
	 *
	 * @param $method
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		$http_method = strtoupper($method);

		if (in_array($http_method, self::$methods))
		{
			array_unshift($arguments, $http_method);

			return call_user_func_array([ $this, 'send' ], $arguments);
		}

		throw new MethodNotDefined($method, $this);
	}

	/**
	 * Checks if the specified param exists in the request's params.
	 *
	 * @param string $param The name of the parameter.
	 *
	 * @return bool
	 */
	public function offsetExists($param)
	{
		return isset($this->params[$param]);
	}

	/**
	 * Get the specified param from the request's params.
	 *
	 * @param string $param The name of the parameter.
	 *
	 * @return mixed|null The value of the parameter, or `null` if the parameter does not exists.
	 */
	public function offsetGet($param)
	{
		return isset($this->params[$param]) ? $this->params[$param] : null;
	}

	/**
	 * Set the specified param to the specified value.
	 *
	 * @param string $param The name of the parameter.
	 * @param mixed $value The value of the parameter.
	 */
	public function offsetSet($param, $value)
	{
		$this->params;
		$this->params[$param] = $value;
		$this->request_params[$param] = $value;
	}

	/**
	 * Remove the specified param from the request's parameters.
	 *
	 * @param mixed $param
	 */
	public function offsetUnset($param)
	{
		unset($this->params[$param]);
	}

	/**
	 * Returns an array iterator for the params.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->params);
	}

	/**
	 * Returns the parent request.
	 *
	 * @return Request
	 */
	protected function get_parent()
	{
		return $this->parent;
	}

	/**
	 * Returns the request's context.
	 *
	 * @return Request\Context
	 */
	protected function get_context()
	{
		return $this->context;
	}

	/**
	 * Returns the `Cache-Control` header.
	 *
	 * @return Headers\CacheControl
	 */
	protected function get_cache_control()
	{
		return $this->headers['Cache-Control'];
	}

	/**
	 * Returns the script name.
	 *
	 * The setter is volatile, the value is returned from the ENV key `SCRIPT_NAME`.
	 *
	 * @return string
	 */
	protected function get_script_name()
	{
		return $this->env['SCRIPT_NAME'];
	}

	/**
	 * Returns the request method.
	 *
	 * This is the getter for the `method` magic property.
	 *
	 * The method is retrieved from {@link $env}, if the key `REQUEST_METHOD` is not defined,
	 * the method defaults to {@link METHOD_GET}.
	 *
	 * @return string
	 */
	protected function get_method()
	{
		$method = isset($this->env['REQUEST_METHOD']) ? $this->env['REQUEST_METHOD'] : self::METHOD_GET;

		if ($method == self::METHOD_POST && !empty($this->request_params['_method']))
		{
			$method = strtoupper($this->request_params['_method']);
		}

		return $method;
	}

	/**
	 * Returns the query string of the request.
	 *
	 * The value is obtained from the `QUERY_STRING` key of the {@link $env} array.
	 *
	 * @return string|null
	 */
	protected function get_query_string()
	{
		return isset($this->env['QUERY_STRING']) ? $this->env['QUERY_STRING'] : null;
	}

	/**
	 * Returns the content length of the request.
	 *
	 * The value is obtained from the `CONTENT_LENGTH` key of the {@link $env} array.
	 *
	 * @return int|null
	 */
	protected function get_content_length()
	{
		return isset($this->env['CONTENT_LENGTH']) ? $this->env['CONTENT_LENGTH'] : null;
	}

	/**
	 * Returns the referer of the request.
	 *
	 * The value is obtained from the `HTTP_REFERER` key of the {@link $env} array.
	 *
	 * @return string|null
	 */
	protected function get_referer()
	{
		return isset($this->env['HTTP_REFERER']) ? $this->env['HTTP_REFERER'] : null;
	}

	/**
	 * Returns the user agent of the request.
	 *
	 * The value is obtained from the `HTTP_USER_AGENT` key of the {@link $env} array.
	 *
	 * @return string|null
	 */
	protected function get_user_agent()
	{
		return isset($this->env['HTTP_USER_AGENT']) ? $this->env['HTTP_USER_AGENT'] : null;
	}

	/**
	 * Checks if the request method is `DELETE`.
	 *
	 * @return boolean
	 */
	protected function get_is_delete()
	{
		return $this->method == self::METHOD_DELETE;
	}

	/**
	 * Checks if the request method is `GET`.
	 *
	 * @return boolean
	 */
	protected function get_is_get()
	{
		return $this->method == self::METHOD_GET;
	}

	/**
	 * Checks if the request method is `HEAD`.
	 *
	 * @return boolean
	 */
	protected function get_is_head()
	{
		return $this->method == self::METHOD_HEAD;
	}

	/**
	 * Checks if the request method is `OPTIONS`.
	 *
	 * @return boolean
	 */
	protected function get_is_options()
	{
		return $this->method == self::METHOD_OPTIONS;
	}

	/**
	 * Checks if the request method is `PATCH`.
	 *
	 * @return boolean
	 */
	protected function get_is_patch()
	{
		return $this->method == self::METHOD_PATCH;
	}

	/**
	 * Checks if the request method is `POST`.
	 *
	 * @return boolean
	 */
	protected function get_is_post()
	{
		return $this->method == self::METHOD_POST;
	}

	/**
	 * Checks if the request method is `PUT`.
	 *
	 * @return boolean
	 */
	protected function get_is_put()
	{
		return $this->method == self::METHOD_PUT;
	}

	/**
	 * Checks if the request method is `TRACE`.
	 *
	 * @return boolean
	 */
	protected function get_is_trace()
	{
		return $this->method == self::METHOD_TRACE;
	}

	/**
	 * Checks if the request is a `XMLHTTPRequest`.
	 *
	 * @return boolean
	 */
	protected function get_is_xhr()
	{
		return !empty($this->env['HTTP_X_REQUESTED_WITH']) && preg_match('/XMLHttpRequest/', $this->env['HTTP_X_REQUESTED_WITH']);
	}

	/**
	 * Checks if the request is local.
	 *
	 * @return boolean
	 */
	protected function get_is_local()
	{
		$ip = $this->ip;

		if ($ip == '::1' || preg_match('/^127\.0\.0\.\d{1,3}$/', $ip))
		{
			return true;
		}

		return preg_match('/^0:0:0:0:0:0:0:1(%.*)?$/', $ip);
	}

	/**
	 * Returns the remote IP of the request.
	 *
	 * If defined, the `HTTP_X_FORWARDED_FOR` header is used to retrieve the original IP.
	 *
	 * If the `REMOTE_ADDR` header is empty the request is considered local thus `::1` is returned.
	 *
	 * @see http://en.wikipedia.org/wiki/X-Forwarded-For
	 *
	 * @return string
	 */
	protected function get_ip()
	{
		$forwarded_for = $this->headers['X-Forwarded-For'];

		if ($forwarded_for)
		{
			list($ip) = explode(',', $forwarded_for);

			return $ip;
		}

		return (isset($this->env['REMOTE_ADDR']) ? $this->env['REMOTE_ADDR'] : null) ?: '::1';
	}

	protected function get_authorization()
	{
		if (isset($this->env['HTTP_AUTHORIZATION']))
		{
			return $this->env['HTTP_AUTHORIZATION'];
		}
		else if (isset($this->env['X-HTTP_AUTHORIZATION']))
		{
			return $this->env['X-HTTP_AUTHORIZATION'];
		}
		else if (isset($this->env['X_HTTP_AUTHORIZATION']))
		{
			return $this->env['X_HTTP_AUTHORIZATION'];
		}
		else if (isset($this->env['REDIRECT_X_HTTP_AUTHORIZATION']))
		{
			return $this->env['REDIRECT_X_HTTP_AUTHORIZATION'];
		}

		return null;
	}

	/**
	 * Returns the `REQUEST_URI` environment key.
	 *
	 * If the `REQUEST_URI` key is not defined by the environment, the value is fetched from
	 * the `$_SERVER` array. If the key is not defined in the `$_SERVER` array `null` is returned.
	 *
	 * @return string
	 */
	protected function get_uri()
	{
		return isset($this->env['REQUEST_URI'])
			? $this->env['REQUEST_URI']
			: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null);
	}

	/**
	 * Returns the port of the request.
	 *
	 * @return int
	 */
	protected function get_port()
	{
		return $this->env['REQUEST_PORT'];
	}

	/**
	 * Returns the path of the request, that is the `REQUEST_URI` without the query string.
	 *
	 * @return string
	 */
	protected function get_path()
	{
		$uri = $this->uri;
		$qs_pos = strpos($uri, '?');

		return ($qs_pos === false) ? $uri : substr($uri, 0, $qs_pos);
	}

	/**
	 * Returns the {@link $path} property normalized using the
	 * {@link \ICanBoogie\normalize_url_path()} function.
	 *
	 * @return string
	 */
	protected function get_normalized_path()
	{
		return \ICanBoogie\normalize_url_path($this->path);
	}

	/**
	 * Returns the extension of the path info.
	 *
	 * @return mixed
	 */
	protected function get_extension()
	{
		return pathinfo($this->path, PATHINFO_EXTENSION);
	}

	protected function lazy_set_params($params)
	{
		return $params;
	}

	/**
	 * Returns the union of the {@link path_params}, {@link request_params} and
	 * {@link query_params} properties.
	 *
	 * This method is the getter of the {@link $params} magic property.
	 *
	 * @return array
	 */
	protected function lazy_get_params()
	{
		return $this->path_params + $this->request_params + $this->query_params;
	}
}
