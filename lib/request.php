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

/**
 * An HTTP request.
 *
 * @method Response connect() connect(array $params)
 * @method Response delete() delete(array $params)
 * @method Response get() get(array $params)
 * @method Response head() head(array $params)
 * @method Response options() options(array $params)
 * @method Response post() post(array $params)
 * @method Response put() put(array $params)
 * @method Response patch() patch(array $params)
 * @method Response trace() trace(array $params)
 *
 * @property-read \ICanBoogie\HTTP\Request\Context $context the request's context.
 * @property-read Request $previous Previous request.
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
 * @property string $method Method of the request.
 * @property-read string $normalized_path Path of the request normalized using the {@link \ICanBoogie\normalize_url_path()} function.
 * @property-read string $path Path info of the request.
 * @property int $port Port of the request.
 * @property-read string $query_string Query string of the request.
 * @property-read string $script_name Name of the entered script.
 * @property-read string $referer Referer of the request.
 * @property-read string $user_agent User agent of the request.
 * @property string $uri URI of the request.
 *
 * @see http://en.wikipedia.org/wiki/Uniform_resource_locator
 */
class Request extends \ICanBoogie\Object implements \ArrayAccess, \IteratorAggregate
{
	/*
	 * HTTP methods as defined by the {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html Hypertext Transfert protocol 1.1}.
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

	static public $methods = array
	(
		self::METHOD_CONNECT,
		self::METHOD_DELETE,
		self::METHOD_GET,
		self::METHOD_HEAD,
		self::METHOD_OPTIONS,
		self::METHOD_POST,
		self::METHOD_PUT,
		self::METHOD_PATCH,
		self::METHOD_TRACE
	);

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
	public $path_params = array();

	/**
	 * Parameters defined by the query string.
	 *
	 * @var array
	 */
	public $query_params = array();

	/**
	 * Parameters defined by the request body.
	 *
	 * @var array
	 */
	public $request_params = array();

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
	 * Previous request.
	 *
	 * @var Request
	 */
	protected $previous;

	/**
	 * A request can be created from the `$_SERVER` super global array. In that case `$_SERVER` is
	 * used as environment and the request is created with the following properties:
	 *
	 * - {@link $cookie}: a reference to the `$_COOKIE` super global array.
	 * - {@link $path_params}: initialized to an empty array.
	 * - {@link $query_params}: a reference to the `$_GET` super global array.
	 * - {@link $request_params}: a reference to the `$_POST` super global array.
	 *
	 * @param array $properties
	 * @param array $construct_args
	 * @param string $class_name
	 *
	 * @return Request
	 */
	static public function from($properties=null, array $construct_args=array(), $class_name=null)
	{
		if (is_string($properties))
		{
			$properties = array
			(
				'path' => $properties
			);
		}
		else if ($properties == $_SERVER)
		{
			return parent::from
			(
				array
				(
					'cookies' => &$_COOKIE,
					'path_params' => array(),
					'query_params' => &$_GET,
					'request_params' => &$_POST
				),

				array($_SERVER)
			);
		}

		return parent::from($properties, $construct_args, $class_name);
	}

	/**
	 * Initialize the properties {@link $env}, {@link $headers} and {@link $context}.
	 *
	 * If the {@link $params} property is `null` it is set with an usinon of {@link $path_params},
	 * {@link $request_params} and {@link $query_params}.
	 *
	 * @param array $env Environment of the request, usually the `$_SERVER` super global.
	 */
	protected function __construct(array $env=array())
	{
		$this->env = $env;
		$this->headers = new Headers($env);
		$this->context = new Request\Context($this);

		if ($this->params === null)
		{
 			$this->params = $this->path_params + $this->request_params + $this->query_params;
		}
	}

	/**
	 * Dispatch the request.
	 *
	 * The {@link previous} property is used for request chaining. The {@link $current_request}
	 * class property is set to the current request.
	 *
	 * @param string|null $method The request method. Use this parameter to override the request
	 * method.
	 * @param array|null $params The request parameters. Use this parameter to override the request
	 * parameters. The {@link $path_params}, {@link $query_params} and
	 * {@link $request_params} are set to empty arrays. The provided parameters are set to the
	 * {@link $params} property.
	 *
	 * Note: If an exception is thrown during dispatch {@link $current_request} is not updated!
	 *
	 * @return Response The response to the request.
	 */
	public function __invoke($method=null, $params=null)
	{
		global $core;

		if ($method !== null)
		{
			$this->method = $method;
		}

		if ($params !== null)
		{
			$this->path_params = array();
			$this->query_params = array();
			$this->request_params = array();
			$this->params = $params;
		}

		$this->previous = self::$current_request;

		self::$current_request = $this;

		$response = dispatch($this);

		self::$current_request = $this->previous;

		return $response;
	}

	/**
	 * Handles read-only properties.
	 *
	 * @throws PropertyNotWritable in attempt to write boolean properties `is_*`or one of the
	 * following properties: {@link $ip}, {@link $authorization}, {@link $path},
	 * {@link $normalized_path}, {@link $extension}.
	 */
	public function __set($property, $value)
	{
		static $readonly = array
		(
			'ip',
			'authorization',
			'path',
			'normalized_path',
			'extension'
		);

		if (strpos($property, 'is_') === 0 || in_array($property, $readonly))
		{
			throw new PropertyNotWritable(array($property, $this));
		}

		parent::__set($property, $value);
	}

	/**
	 * Overrides the method to provide a virtual method for each request method.
	 *
	 * Example:
	 *
	 * <pre>
	 * Request::from(array('path' => '/api/core/aloha'))->get();
	 * </pre>
	 */
	public function __call($method, $arguments)
	{
		$http_method = strtoupper($method);

		if (in_array($http_method, self::$methods))
		{
			array_unshift($arguments, $http_method);

			return call_user_func_array(array($this, '__invoke'), $arguments);
		}

		return parent::__call($method, $arguments);
	}

	/**
	 * Checks if the specified param exists in the request.
	 */
	public function offsetExists($param)
	{
		return isset($this->params[$param]);
	}

	/**
	 * Get the specified param from the request.
	 */
	public function offsetGet($param)
	{
		return isset($this->params[$param]) ? $this->params[$param] : null;
	}

	/**
	 * Set the specified param to the specified value.
	 */
	public function offsetSet($param, $value)
	{
		$this->params[$param] = $value;
	}

	/**
	 * Remove the specified param from the request.
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
	 * Returns the previous request.
	 *
	 * @return \ICanBoogie\HTTP\Request
	 */
	protected function volatile_get_previous()
	{
		return $this->previous;
	}

	/**
	 * Returns the request's context.
	 *
	 * @return Request\Context
	 */
	protected function volatile_get_context()
	{
		return $this->context;
	}

	/**
	 * Returns the `Cache-Control` header.
	 *
	 * @return \ICanBoogie\HTTP\Headers\CacheControl
	 */
	protected function volatile_get_cache_control()
	{
		return $this->headers['Cache-Control'];
	}

	/**
	 * Sets the directives of the `Cache-Control` header.
	 *
	 * @param string $cache_directives
	 */
	protected function volatile_set_cache_control($cache_directives)
	{
		$this->headers['Cache-Control'] = $cache_directives;
	}

	/**
	 * Returns the script name.
	 *
	 * The setter is volatile, the value is returned from the ENV key `SCRIPT_NAME`.
	 *
	 * @return string
	 */
	protected function volatile_get_script_name()
	{
		return $this->env['SCRIPT_NAME'];
	}

	/**
	 * Sets the script name.
	 *
	 * The setter is volatile, the value is set to the ENV key `SCRIPT_NAME`.
	 *
	 * @param string $value
	 */
	protected function volatile_set_script_name($value)
	{
		$this->env['SCRIPT_NAME'] = $value;
	}

	/**
	 * Returns the request method.
	 *
	 * This is the getter for the `method` magic property.
	 *
	 * The method is retrieved from {@link $env}, if the key `REQUEST_METHOD` is not defined,
	 * the method defaults to {@link METHOD_GET}.
	 *
	 * @throws MethodNotSupported when the request method is not supported.
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

		if (!in_array($method, self::$methods))
		{
			throw new MethodNotSupported($method);
		}

		return $method;
	}

	/**
	 * Sets the `QUERY_STRING` value of the {@link $env} array.
	 *
	 * @param string $query_string
	 */
	protected function volatile_set_query_string($query_string)
	{
		$this->env['QUERY_STRING'] = $query_string;
	}

	/**
	 * Returns the `QUERY_STRING` value of the {@link $env} array.
	 *
	 * @param string $query_string The method returns `null` if the key is not defined.
	 */
	protected function volatile_get_query_string()
	{
		return isset($this->env['QUERY_STRING']) ? $this->env['QUERY_STRING'] : null;
	}

	protected function volatile_get_content_length()
	{
		return isset($this->env['CONTENT_LENGTH']) ? $this->env['CONTENT_LENGTH'] : null;
	}

	protected function volatile_get_referer()
	{
		return isset($this->env['HTTP_REFERER']) ? $this->env['HTTP_REFERER'] : null;
	}

	protected function volatile_get_user_agent()
	{
		return isset($this->env['HTTP_USER_AGENT']) ? $this->env['HTTP_USER_AGENT'] : null;
	}

	/**
	 * Checks if the request method is `DELETE`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_delete()
	{
		return $this->method == 'delete';
	}

	/**
	 * Checks if the request method is `GET`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_get()
	{
		return $this->method == self::METHOD_GET;
	}

	/**
	 * Checks if the request method is `HEAD`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_head()
	{
		return $this->method == self::METHOD_HEAD;
	}

	/**
	 * Checks if the request method is `OPTIONS`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_options()
	{
		return $this->method == self::METHOD_OPTIONS;
	}

	/**
	 * Checks if the request method is `PATCH`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_patch()
	{
		return $this->method == self::METHOD_PATCH;
	}

	/**
	 * Checks if the request method is `POST`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_post()
	{
		return $this->method == self::METHOD_POST;
	}

	/**
	 * Checks if the request method is `PUT`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_put()
	{
		return $this->method == self::METHOD_PUT;
	}

	/**
	 * Checks if the request method is `TRACE`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_trace()
	{
		return $this->method == self::METHOD_TRACE;
	}

	/**
	 * Checks if the request is a `XMLHTTPRequest`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_xhr()
	{
		return !empty($this->env['HTTP_X_REQUESTED_WITH']) && preg_match('/XMLHttpRequest/', $this->env['HTTP_X_REQUESTED_WITH']);
	}

	/**
	 * Checks if the request is local.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_local()
	{
		static $patterns = array('::1', '/^127\.0\.0\.\d{1,3}$/', '/^0:0:0:0:0:0:0:1(%.*)?$/');

		$ip = $this->ip;

		foreach ($patterns as $pattern)
		{
			if ($pattern{0} == '/')
			{
				if (preg_match($pattern, $ip))
				{
					return true;
				}
			}
			else if ($pattern == $ip)
			{
				return true;
			}
		}

		return false;
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
	protected function volatile_get_ip()
	{
		if (isset($this->env['HTTP_X_FORWARDED_FOR']))
		{
			$addr = $this->env['HTTP_X_FORWARDED_FOR'];

			list($addr) = explode(',', $addr);

			return $addr;
		}

		return $this->env['REMOTE_ADDR'] ?: '::1';
	}

	protected function volatile_get_authorization()
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
	}

	/**
	 * Sets the `REQUEST_URI` environment key.
	 *
	 * The {@link $path} and {@link $query_string} property are unset so that they are updated on
	 * there next access.
	 *
	 * @param string $uri
	 */
	protected function volatile_set_uri($uri)
	{
		unset($this->path);
		unset($this->query_string);

		$this->env['REQUEST_URI'] = $uri;
	}

	/**
	 * Returns the `REQUEST_URI` environment key.
	 *
	 * @return string
	 */
	protected function volatile_get_uri()
	{
		return isset($this->env['REQUEST_URI']) ? $this->env['REQUEST_URI'] : $_SERVER['REQUEST_URI'];
	}

	/**
	 * Sets the port of the request.
	 *
	 * @param int $port
	 */
	protected function volatile_set_port($port)
	{
		$this->env['REQUEST_PORT'] = $port;
	}

	/**
	 * Returns the port of the request.
	 *
	 * @return int
	 */
	protected function volatile_get_port()
	{
		return $this->env['REQUEST_PORT'];
	}

	/**
	 * Returns the path of the request, that is the `REQUEST_URI` without the query string.
	 *
	 * @return string
	 */
	protected function volatile_get_path()
	{
		$path = $this->env['REQUEST_URI'];
		$qs = $this->query_string;

		if ($qs)
		{
			$path = substr($path, 0, -(strlen($qs) + 1));
		}

		return $path;
	}

	/**
	 * Returns the {@link $path} property normalized using the
	 * {@link \ICanBoogie\normalize_url_path()} function.
	 *
	 * @return string
	 */
	protected function volatile_get_normalized_path()
	{
		return \ICanBoogie\normalize_url_path($this->path);
	}

	/**
	 * Returns the extension of the path info.
	 *
	 * @return mixed
	 */
	protected function volatile_get_extension()
	{
		return pathinfo($this->path, PATHINFO_EXTENSION);
	}

	protected function set_params($params)
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
	protected function get_params()
	{
		return $this->path_params + $this->request_params + $this->query_params;
	}

	/**
	 * @throws PropertyNotWritable in attempt to write an unsupported property.
	 */
	/*
	protected function last_chance_set($property, $value, &$success)
	{
		throw new PropertyNotWritable(array($property, $this));
	}
	*/
}

namespace ICanBoogie\HTTP\Request;

use ICanBoogie\PropertyNotWritable;

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 *
 * @property-read \ICanBoogie\HTTP\Request $request The request associated with the context.
 * @property-read \ICanBoogie\HTTP\IDispatcher $dispatcher The dispatcher currently dispatching the request.
 */
class Context extends \ICanBoogie\Object
{
	/**
	 * The request the context belongs to.
	 *
	 * The variable is declared as private but is actually readable thanks to the
	 * {@link volatile_get_request} getter.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	private $request;

	/**
	 * The dispatcher currently dispatching the request.
	 *
	 * @var \ICanBoogie\HTTP\IDispatcher|null
	 */
	private $dispatcher;

	/**
	 * Constructor.
	 *
	 * @param \ICanBoogie\HTTP\Request $request The request the context belongs to.
	 */
	public function __construct(\ICanBoogie\HTTP\Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @throws PropertyNotWritable in attempt to write {@link $request}
	 */
	protected function volatile_set_request()
	{
		throw new PropertyNotWritable(array('request', $this));
	}

	/**
	 * Returns the {@link $request} property.
	 *
	 * @return \ICanBoogie\HTTP\Request
	 */
	protected function volatile_get_request()
	{
		return $this->request;
	}

	/**
	 * Sets the dispatcher currently dispatching the request.
	 *
	 * @param \ICanBoogie\HTTP\IDispatcher|null $dispatcher
	 *
	 * @throws \InvalidArgumentException if the value is not null and does not implements \ICanBoogie\HTTP\IDispatcher.
	 */
	protected function volatile_set_dispatcher($dispatcher)
	{
		if ($dispatcher !== null && !($dispatcher instanceof \ICanBoogie\HTTP\IDispatcher))
		{
			throw new \InvalidArgumentException('$dispatcher must be an instance of ICanBoogie\HTTP\IDispatcher. Given: ' . get_class($dispatcher) . '.');
		}

		$this->dispatcher = $dispatcher;
	}

	/**
	 * Returns the {@link $dispatcher} property.
	 *
	 * @return \ICanBoogie\HTTP\IDispatcher
	 */
	protected function volatile_get_dispatcher()
	{
		return $this->dispatcher;
	}
}