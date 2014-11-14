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

/**
 * A response to a HTTP request.
 *
 * @property integer $status The HTTP status code.
 * See: {@link set_status()} {@link get_status()}
 * @property string $status_message The HTTP status message.
 * See: {@link set_status_message()} {@link get_status_message()}
 * @property int $ttl Adjusts the `s-maxage` part of the `Cache-Control` header field definition according to the `Age` header field definition.
 * See: {@link set_ttl()} {@link get_ttl()}
 *
 * @property int $age Shortcut to the `Age` header field definition.
 * See: {@link set_age()} {@link get_age()}
 * @property \ICanBoogie\HTTP\Headers\CacheControl $cache_control Shortcut to the `Cache-Control` header field definition.
 * See: {@link set_cache_control()} {@link get_cache_control()}
 * @property int $content_length Shortcut to the `Content-Length` header field definition.
 * See: {@link set_content_length()} {@link get_content_length()}
 * @property \ICanBoogie\HTTP\Headers\ContentType $content_type Shortcut to the `Content-Type` header field definition.
 * See: {@link set_content_type()} {@link get_content_type()}
 * @property \ICanBoogie\HTTP\Headers\Date $date Shortcut to the `Date` header field definition.
 * See: {@link set_date()} {@link get_date()}
 * @property string $etag Shortcut to the `Etag` header field definition.
 * See: {@link set_etag()} {@link get_etag()}
 * @property \ICanBoogie\HTTP\Headers\Date $expires Shortcut to the `Expires` header field definition.
 * See: {@link set_expires()} {@link get_expires()}
 * @property \ICanBoogie\HTTP\Headers\Date $last_modified Shortcut to the `Last-Modified` header field definition.
 * See: {@link set_last_modified()} {@link get_last_modified()}
 * @property string $location Shortcut to the `Location` header field definition.
 * See: {@link set_location()} {@link get_location()}
 *
 * @property string|\Closure $body The body of the response.
 * See: {@link set_body()} {@link get_body()}
 *
 * @property-read boolean $is_cacheable {@link get_is_cacheable()}
 * @property-read boolean $is_client_error {@link get_is_client_error()}
 * @property-read boolean $is_empty {@link get_is_empty()}
 * @property-read boolean $is_forbidden {@link get_is_forbidden()}
 * @property-read boolean $is_fresh {@link get_is_fresh()}
 * @property-read boolean $is_informational {@link get_is_informational()}
 * @property-read boolean $is_not_found {@link get_is_not_found()}
 * @property-read boolean $is_ok {@link get_is_ok()}
 * @property-read boolean $is_private {@link get_is_private()}
 * @property-read boolean $is_redirect {@link get_is_redirect()}
 * @property-read boolean $is_server_error {@link get_is_server_error()}
 * @property-read boolean $is_successful {@link get_is_successful()}
 * @property-read boolean $is_valid {@link get_is_valid()}
 * @property-read boolean $is_validateable {@link get_is_validateable()}
 *
 * @see http://tools.ietf.org/html/rfc2616
 */
class Response
{
	use \ICanBoogie\PrototypeTrait;

	/**
	 * HTTP status messages.
	 *
	 * @var array[int]string
	 */
	static public $status_messages = [

		100 => "Continue",
		101 => "Switching Protocols",

		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",

		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",

		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",
		418 => "I'm a teapot",

		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported"

	];

	/**
	 * Response headers.
	 *
	 * @var Headers
	 */
	public $headers;

	/**
	 * The HTTP protocol version (1.0 or 1.1), defaults to '1.0'
	 *
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * Initializes the {@link $body}, {@link $header}, {@link $date} and {@link $status}
	 * properties.
	 *
	 * @param mixed $body The body of the response.
	 * @param int $status The status code of the response.
	 * @param Headers|array $headers The initial header fields of the response.
	 */
	public function __construct($body=null, $status=200, $headers=[])
	{
		if (is_array($headers))
		{
			$headers = new Headers($headers);
		}
		else if (!($headers instanceof Headers))
		{
			throw new \InvalidArgumentException("$headers must be an array or a ICanBoogie\HTTP\Headers instance. Given: " . gettype($headers));
		}

		$this->headers = $headers;

		if ($this->date->is_empty)
		{
			$this->date = 'now';
		}

		$this->set_status($status);

		if ($body !== null)
		{
			$this->set_body($body);
		}
	}

	/**
	 * Clones the {@link $headers] property.
	 */
	public function __clone()
	{
		$this->headers = clone $this->headers;
	}

	/**
	 * Renders the response as an HTTP string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		try
		{
			$header = clone $this->headers;
			$body = $this->body;

			$this->finalize($header, $body);

			ob_start();

			$this->echo_body($body);

			$body = ob_get_clean();

			return "HTTP/{$this->version} {$this->status} {$this->status_message}\r\n"
			. $header
			. "\r\n"
			. $body;
		}
		catch (\Exception $e)
		{
			return (string) $e;
		}
	}

	/**
	 * Issues the HTTP response.
	 *
	 * The header is modified according to the {@link version}, {@link status} and
	 * {@link status_message} properties.
	 *
	 * The usual behavior of the response is to echo its body and then terminate the script. But
	 * if its body is `null` the following happens :
	 *
	 * - If the {@link $location} property is defined the script is terminated.
	 *
	 * - If the {@link $is_ok} property is falsy **the method returns**.
	 *
	 * Note: If the body is a string, or an object implementing the `__toString()` method, the
	 * `Content-Length` header is automatically defined to the length of the body string.
	 *
	 * Note: If the body is an instance of {@link Closure} it MUST echo the response's body.
	 */
	public function __invoke()
	{
		$headers = clone $this->headers;
		$body = $this->body;

		$this->finalize($headers, $body);

		#
		# send headers
		#

		if (headers_sent($file, $line))
		{
			trigger_error(\ICanBoogie\format
			(
				"Cannot modify header information because it was already sent. Output started at !at.", [

					'at' => $file . ':' . $line

				]
			));
		}
		else
		{
			header_remove('Pragma');
			header_remove('X-Powered-By');

			header("HTTP/{$this->version} {$this->status} {$this->status_message}");

			$headers();
		}

		if ($body === null && ($this->location || !$this->is_ok))
		{
			return;
		}

		$this->echo_body($body);
	}

	/**
	 * Finalize the body.
	 *
	 * If the body is a string or can be converted into a string the `Content-Length` header is
	 * added with the length of that string.
	 *
	 * Subclasses might want to override this method if they wish to alter the header or the body
	 * before the response is sent or transformed into a string.
	 *
	 * @param Header $headers Reference to the final header.
	 * @param mixed $body Reference to the final body.
	 */
	protected function finalize(Headers &$headers, &$body)
	{
		if (is_object($body) && method_exists($body, '__toString'))
		{
			$body = (string) $body;
		}

		if (empty($headers['Content-Length']) && $body && !is_object($body) && !($body instanceof \Closure))
		{
			$headers['Content-Length'] = strlen($body);
		}
	}

	/**
	 * Status of the HTTP response.
	 *
	 * @var int
	 */
	private $status;

	/**
	 * Message describing the status code.
	 *
	 * @var string
	 */
	public $status_message;

	/**
	 * Sets response status code and optionally status message.
	 *
	 * This method is the setter for the {@link $status} property.
	 *
	 * @param integer|array $status HTTP status code or HTTP status code and HTTP status message.
	 *
	 * @throws \InvalidArgumentException When the HTTP status code is not valid.
	 */
	protected function set_status($status)
	{
		$status_message = null;

		if (is_array($status))
		{
			list($status, $status_message) = $status;
		}

		$this->status = (int) $status;

		if (!$this->is_valid)
		{
			throw new StatusCodeNotValid($status);
		}

		if ($status_message === null)
		{
			unset($this->status_message);
		}
		else
		{
			$this->status_message = $status_message;
		}
	}

	/**
	 * Returns the response status code.
	 *
	 * This method is the getter for the {@link $status} property.
	 *
	 * @return integer
	 */
	protected function get_status()
	{
		return $this->status;
	}

	/**
	 * The response body.
	 *
	 * @var mixed
	 *
	 * @see set_body(), get_body()
	 */
	private $body;

	/**
	 * Sets the response body.
	 *
	 * The body can be any data type that can be converted into a string. This includes numeric
	 * and objects implementing the {@link __toString()} method.
	 *
	 * **Note**: If the length of `$body` can be determined, the `Content-Length` header field is
	 * updated. Also, `Content-Length` is set to `null` if `$body` is `null`.
	 *
	 * @param string|\Closure|null $body
	 *
	 * @throws \UnexpectedValueException when the body cannot be converted to a string.
	 */
	protected function set_body($body)
	{
		if ($body !== null
		&& !($body instanceof \Closure)
		&& !is_numeric($body)
		&& !is_string($body)
		&& !(is_object($body) && method_exists($body, '__toString')))
		{
			throw new \UnexpectedValueException(\ICanBoogie\format
			(
				'The Response body must be a string, an object implementing the __toString() method or be callable, %type given. !value', array
				(
					'type' => gettype($body),
					'value' => $body
				)
			));
		}

		if ($body === null || $body instanceof \Closure || is_object($body))
		{
			$this->content_length = null;
		}
		else
		{
			$this->content_length = strlen($body);
		}

		$this->body = $body;
	}

	/**
	 * Returns the response body.
	 *
	 * Note: This method is the getter for the {@link $body} property.
	 *
	 * @return string
	 */
	protected function get_body()
	{
		return $this->body;
	}

	/**
	 * Echo the body.
	 *
	 * @param mixed $body
	 */
	protected function echo_body($body)
	{
		if ($body instanceof \Closure)
		{
			$body($this);
		}
		else
		{
			echo $body;
		}
	}

	/**
	 * Returns the message associated with the status code.
	 *
	 * This method is the volatile getter for the {@link $status_message} property and is only
	 * called if the property is not accessible.
	 *
	 * @return string
	 */
	protected function get_status_message()
	{
		return self::$status_messages[$this->status];
	}

	/**
	 * Sets the value of the `Location` header field.
	 *
	 * @param string|null $url
	 */
	protected function set_location($url)
	{
		if ($url !== null && !$url)
		{
			throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
		}

		$this->headers['Location'] = $url;
	}

	/**
	 * Returns the value of the `Location` header field.
	 *
	 * @return string
	 */
	protected function get_location()
	{
		return $this->headers['Location'];
	}

	/**
	 * Sets the value of the `Content-Type` header field.
	 *
	 * @param string $content_type
	 */
	protected function set_content_type($content_type)
	{
		$this->headers['Content-Type'] = $content_type;
	}

	/**
	 * Returns the value of the `Content-Type` header field.
	 *
	 * @return Headers\ContentType
	 */
	protected function get_content_type()
	{
		return $this->headers['Content-Type'];
	}

	/**
	 * Sets the value of the `Content-Length` header field.
	 *
	 * @param int $length
	 */
	protected function set_content_length($length)
	{
		$this->headers['Content-Length'] = $length;
	}

	/**
	 * Returns the value of the `Content-Length` header field.
	 *
	 * @return int
	 */
	protected function get_content_length()
	{
		return $this->headers['Content-Length'];
	}

	/**
	 * Sets the value of the `Date` header field.
	 *
	 * @param mixed $time
	 */
	protected function set_date($time)
	{
		$this->headers['Date'] = $time;
	}

	/**
	 * Returns the value of the `Date` header field.
	 *
	 * @return Headers\Date
	 */
	protected function get_date()
	{
		return $this->headers['Date'];
	}

	/**
	 * Sets the value of the `Age` header field.
	 *
	 * @param int $age
	 */
	protected function set_age($age)
	{
		$this->headers['Age'] = $age;
	}

	/**
	 * Returns the age of the response.
	 *
	 * @return int
	 */
	protected function get_age()
	{
		$age = $this->headers['Age'];

		if ($age)
		{
			return $age;
		}

		return max(time() - $this->date->format('U'), 0);
	}

	/**
	 * Sets the value of the `Last-Modified` header field.
	 *
	 * @param mixed $time.
	 */
	protected function set_last_modified($time)
	{
		$this->headers['Last-Modified'] = $time;
	}

	/**
	 * Returns the value of the `Last-Modified` header field.
	 *
	 * @return Headers\Date
	 */
	protected function get_last_modified()
	{
		return $this->headers['Last-Modified'];
	}

	/**
	 * Sets the value of the `Expires` header field.
	 *
	 * The method also calls the {@link session_cache_expire()} function.
	 *
	 * @param mixed $time.
	 */
	protected function set_expires($time)
	{
		$this->headers['Expires'] = $time;

		session_cache_expire($time); // TODO-20120831: Is this required now that we have an awesome response system ?
	}

	/**
	 * Returns the value of the `Expires` header field.
	 *
	 * @return Headers\Date
	 */
	protected function get_expires()
	{
		return $this->headers['Expires'];
	}

	/**
	 * Sets the value of the `Etag` header field.
	 *
	 * @param string $value
	 */
	protected function set_etag($value)
	{
		$this->headers['Etag'] = $value;
	}

	/**
	 * Returns the value of the `Etag` header field.
	 *
	 * @return string
	 */
	protected function get_etag()
	{
		return $this->headers['Etag'];
	}

	/**
	 * Sets the directives of the `Cache-Control` header field.
	 *
	 * @param string $cache_directives
	 */
	protected function set_cache_control($cache_directives)
	{
		$this->headers['Cache-Control'] = $cache_directives;
	}

	/**
	 * Returns the `Cache-Control` header field.
	 *
	 * @return \ICanBoogie\HTTP\Headers\CacheControl
	 */
	protected function get_cache_control()
	{
		return $this->headers['Cache-Control'];
	}

	/**
	 * Sets the response's time-to-live for shared caches.
	 *
	 * This method adjusts the Cache-Control/s-maxage directive.
	 *
	 * @param int $seconds The number of seconds.
	 */
	protected function set_ttl($seconds)
	{
		$this->cache_control->s_max_age = $this->age->timestamp + $seconds;
	}

	/**
	 * Returns the response's time-to-live in seconds.
	 *
	 * When the responses TTL is <= 0, the response may not be served from cache without first
	 * re-validating with the origin.
	 *
	 * @return int|null The number of seconds to live, or `null` is no freshness information
	 * is present.
	 */
	protected function get_ttl()
	{
		$max_age = $this->cache_control->max_age;

		if ($max_age)
		{
			return $max_age - $this->age;
		}
	}

	/**
	 * Set the `cacheable` property of the `Cache-Control` header field to `private` or `public`.
	 *
	 * @param boolean $value Set `cacheable` to `private` if `true`, `public` if `false`.
	 */
	protected function set_is_private($value)
	{
		$this->cache_control->cacheable = $value ? 'private' : 'public';
	}

	/**
	 * Checks that the `cacheable` property of the `Cache-Control` header field is `private`.
	 *
	 * @return boolean
	 */
	protected function get_is_private()
	{
		return $this->cache_control->cacheable == 'private';
	}

	/**
	 * Checks if the response is valid.
	 *
	 * A response is considered valid when its status is between 100 and 600, 100 included.
	 *
	 * Note: This method is the getter for the `is_valid` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_valid()
	{
		return $this->status >= 100 && $this->status < 600;
	}

	/**
	 * Checks if the response is informational.
	 *
	 * A response is considered informational when its status is between 100 and 200, 100 included.
	 *
	 * Note: This method is the getter for the `is_informational` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_informational()
	{
		return $this->status >= 100 && $this->status < 200;
	}

	/**
	 * Checks if the response is successful.
	 *
	 * A response is considered successful when its status is between 200 and 300, 200 included.
	 *
	 * Note: This method is the getter for the `is_successful` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_successful()
	{
		return $this->status >= 200 && $this->status < 300;
	}

	/**
	 * Checks if the response is a redirection.
	 *
	 * A response is considered to be a redirection when its status is between 300 and 400, 300
	 * included.
	 *
	 * Note: This method is the getter for the `is_redirect` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_redirect()
	{
		return $this->status >= 300 && $this->status < 400;
	}

	/**
	 * Checks if the response is a client error.
	 *
	 * A response is considered a client error when its status is between 400 and 500, 400
	 * included.
	 *
	 * Note: This method is the getter for the `is_client_error` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_client_error()
	{
		return $this->status >= 400 && $this->status < 500;
	}

	/**
	 * Checks if the response is a server error.
	 *
	 * A response is considered a server error when its status is between 500 and 600, 500
	 * included.
	 *
	 * Note: This method is the getter for the `is_server_error` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_server_error()
	{
		return $this->status >= 500 && $this->status < 600;
	}

	/**
	 * Checks if the response is ok.
	 *
	 * A response is considered ok when its status is 200.
	 *
	 * Note: This method is the getter for the `is_ok` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_ok()
	{
		return $this->status == 200;
	}

	/**
	 * Checks if the response is forbidden.
	 *
	 * A response is forbidden ok when its status is 403.
	 *
	 * Note: This method is the getter for the `is_forbidden` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_forbidden()
	{
		return $this->status == 403;
	}

	/**
	 * Checks if the response is not found.
	 *
	 * A response is considered not found when its status is 404.
	 *
	 * Note: This method is the getter for the `is_not_found` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_not_found()
	{
		return $this->status == 404;
	}

	/**
	 * Checks if the response is empty.
	 *
	 * A response is considered empty when its status is 201, 204 or 304.
	 *
	 * Note: This method is the getter for the `is_empty` magic property.
	 *
	 * @return boolean
	 */
	protected function get_is_empty()
	{
		static $range = [ 201, 204, 304 ];

		return in_array($this->status, $range);
	}

	/**
	 * Checks that the response includes header fields that can be used to validate the response
	 * with the origin server using a conditional GET request.
	 *
	 * @return boolean
	 */
	protected function get_is_validateable()
	{
		return $this->headers['Last-Modified'] || $this->headers['ETag'];
	}

	/**
	 * Checks that the response is worth caching under any circumstance.
	 *
	 * Responses marked _private_ with an explicit `Cache-Control` directive are considered
	 * uncacheable.
	 *
	 * Responses with neither a freshness lifetime (Expires, max-age) nor cache validator
	 * (`Last-Modified`, `ETag`) are considered uncacheable.
	 *
	 * @return boolean
	 */
	protected function get_is_cacheable()
	{
		static $range = [ 200, 203, 300, 301, 302, 404, 410 ];

		if (!in_array($this->status, $range))
		{
			return false;
		}

		if ($this->cache_control->no_store || $this->cache_control->cacheable == 'private')
		{
			return false;
		}

		return $this->is_validateable() || $this->is_fresh();
	}

	/**
	 * Checks if the response is fresh.
	 *
	 * @return boolean
	 */
	protected function get_is_fresh()
	{
		return $this->ttl > 0;
	}
}
