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
 * A response to a HTTP request.
 *
 * @property integer $status The HTTP status code.
 * See: {@link volatile_set_status()} {@link volatile_get_status()}
 * @property string $status_message The HTTP status message.
 * See: {@link volatile_set_status_message()} {@link volatile_get_status_message()}
 * @property int $ttl Adjusts the `s-maxage` part of the `Cache-Control` header field definition according to the `Age` header field definition.
 * See: {@link volatile_set_ttl()} {@link volatile_get_ttl()}
 *
 * @property int $age Shortcut to the `Age` header field definition.
 * See: {@link volatile_set_age()} {@link volatile_get_age()}
 * @property \ICanBoogie\HTTP\Headers\CacheControl $cache_control Shortcut to the `Cache-Control` header field definition.
 * See: {@link volatile_set_cache_control()} {@link volatile_get_cache_control()}
 * @property int $content_length Shortcut to the `Content-Length` header field definition.
 * See: {@link volatile_set_content_length()} {@link volatile_get_content_length()}
 * @property \ICanBoogie\HTTP\Headers\ContentType $content_type Shortcut to the `Content-Type` header field definition.
 * See: {@link volatile_set_content_type()} {@link volatile_get_content_type()}
 * @property \ICanBoogie\HTTP\Headers\DateTime $date Shortcut to the `Date` header field definition.
 * See: {@link volatile_set_date()} {@link volatile_get_date()}
 * @property string $etag Shortcut to the `Etag` header field definition.
 * See: {@link volatile_set_etag()} {@link volatile_get_etag()}
 * @property \ICanBoogie\HTTP\Headers\DateTime $expires Shortcut to the `Expires` header field definition.
 * See: {@link volatile_set_expires()} {@link volatile_get_expires()}
 * @property \ICanBoogie\HTTP\Headers\DateTime $last_modified Shortcut to the `Last-Modified` header field definition.
 * See: {@link volatile_set_last_modified()} {@link volatile_get_last_modified()}
 * @property string $location Shortcut to the `Location` header field definition.
 * See: {@link volatile_set_location()} {@link volatile_get_location()}
 *
 * @property string|\Closure $body The body of the response.
 * See: {@link volatile_set_body()} {@link volatile_get_body()}
 *
 * @property-read boolean $is_cacheable {@link volatile_get_is_cacheable()}
 * @property-read boolean $is_client_error {@link volatile_get_is_client_error()}
 * @property-read boolean $is_empty {@link volatile_get_is_empty()}
 * @property-read boolean $is_forbidden {@link volatile_get_is_forbidden()}
 * @property-read boolean $is_fresh {@link volatile_get_is_fresh()}
 * @property-read boolean $is_informational {@link volatile_get_is_informational()}
 * @property-read boolean $is_not_found {@link volatile_get_is_not_found()}
 * @property-read boolean $is_ok {@link volatile_get_is_ok()}
 * @property-read boolean $is_private {@link volatile_get_is_private()}
 * @property-read boolean $is_redirect {@link volatile_get_is_redirect()}
 * @property-read boolean $is_server_error {@link volatile_get_is_server_error()}
 * @property-read boolean $is_successful {@link volatile_get_is_successful()}
 * @property-read boolean $is_valid {@link volatile_get_is_valid()}
 * @property-read boolean $is_validateable {@link volatile_get_is_validateable()}
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616.html
 */
class Response extends \ICanBoogie\Object
{
	/**
	 * HTTP status messages.
	 *
	 * @var array[int]string
	 */
	static public $status_messages = array
	(
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
	);

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
	public function __construct($body=null, $status=200, $headers=array())
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

		if (!$this->headers['Date'])
		{
			$this->date = 'now';
		}

		$this->volatile_set_status($status);
		$this->volatile_set_body($body);
	}

	/**
	 * Handles read-only properties.
	 *
	 * @throws PropertyNotWritable in attempt to write one of the following properties:
	 * {@link $is_valid}, {@link $is_informational}, {@link $is_successful}, {@link $is_redirect),
	 * {@link $is_client_error}, {@link $is_server_error}, {@link $is_ok}, {@link $is_forbidden},
	 * {@link $is_not_found}, {@link $is_empty}, {@link $is_validateable}, {@link $is_cacheable},
	 * {@link $is_fresh}
	 */
	public function __set($property, $value)
	{
		static $readonly = array
		(
			'is_valid',
			'is_informational',
			'is_successful',
			'is_redirect',
			'is_client_error',
			'is_server_error',
			'is_ok',
			'is_forbidden',
			'is_not_found',
			'is_empty',
			'is_validateable',
			'is_cacheable',
			'is_fresh'
		);

		if (in_array($property, $readonly))
		{
			throw new PropertyNotWritable(array($property, $this));
		}

		parent::__set($property, $value);
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
		ob_start();

		$this->echo_body($this->body);

		$body = ob_get_clean();

		return "HTTP/{$this->version} {$this->status} {$this->status_message}\r\n"
		. $this->headers
		. "\r\n"
		. $body;
	}

	/**
	 * Issues the HTTP response.
	 *
	 * The header is modified according to the {@link version}, {@link status} and
	 * {@link status_message} properties.
	 *
	 * The usual behaviour of the response is to echo its body and then terminate the script. But
	 * if its body is `null` the following happens :
	 *
	 * - If the {@link $location} property is defined the script is terminated.
	 *
	 * - If the {@link $is_ok} property is falsy **the method returns**.
	 *
	 * Note: If the body is a string, or an object implementing the `__toString()` method, the
	 * `Content-Length` header is automatically defined to the lenght of the body string.
	 *
	 * Note: If the body is an instance of {@link Closure} it MUST echo the response's body.
	 */
	public function __invoke()
	{
		#
		# If the body is a string we add the `Content-Length`
		#

		$body = $this->body;

		if (is_callable(array($body, '__toString')))
		{
			$body = (string) $body;
		}

		if (is_numeric($body) || is_string($body))
		{
			$this->headers['Content-Length'] = strlen($body);
		}

		#
		# send headers
		#

		if (headers_sent($file, $line))
		{
			trigger_error(\ICanBoogie\format
			(
				"Cannot modify header information because it was already sent. Output started at !at.", array
				(
					'at' => $file . ':' . $line
				)
			));
		}
		else
		{
			header_remove('Pragma');
			header_remove('X-Powered-By');

			header("HTTP/{$this->version} {$this->status} {$this->status_message}");

			$this->headers();
		}

		if ($body === null)
		{
			if ($this->location)
			{
				exit;
			}
			else if (!$this->is_ok)
			{
				return;
			}
		}

		$this->echo_body($body);

		exit;
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
	protected function volatile_set_status($status)
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
	protected function volatile_get_status()
	{
		return $this->status;
	}

	/**
	 * The response body.
	 *
	 * @var mixed
	 *
	 * @see volatile_set_body(), volatile_get_body()
	 */
	private $body;

	/**
	 * Sets the response body.
	 *
	 * The body can be any data type that can be converted into a string this includes numeric and
	 * objects implementing the {@link __toString()} method.
	 *
	 * Note: This method is the setter for the {@link $body} property.
	 *
	 * @param string|\Closure|null $body
	 *
	 * @throws \UnexpectedValueException when the body cannot be converted to a string.
	 */
	protected function volatile_set_body($body)
	{
		if ($body !== null
		&& !($body instanceof \Closure)
		&& !is_numeric($body)
		&& !is_string($body)
		&& !is_callable(array($body, '__toString')))
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

		if ($body === null)
		{
			$this->headers['Content-Length'] = null;
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
	protected function volatile_get_body()
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
	protected function volatile_get_status_message()
	{
		return self::$status_messages[$this->status];
	}

	/**
	 * Sets the value of the `Location` header field.
	 *
	 * @param string|null $url
	 */
	protected function volatile_set_location($url)
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
	protected function volatile_get_location()
	{
		return $this->headers['Location'];
	}

	/**
	 * Sets the value of the `Content-Type` header field.
	 *
	 * @param string $content_type
	 */
	protected function volatile_set_content_type($content_type)
	{
		$this->headers['Content-Type'] = $content_type;
	}

	/**
	 * Returns the value of the `Content-Type` header field.
	 *
	 * @return Headers\ContentType
	 */
	protected function volatile_get_content_type()
	{
		return $this->headers['Content-Type'];
	}

	/**
	 * Sets the value of the `Content-Length` header field.
	 *
	 * @param int $length
	 */
	protected function volatile_set_content_length($length)
	{
		$this->headers['Content-Length'] = $length;
	}

	/**
	 * Returns the value of the `Content-Length` header field.
	 *
	 * @return int
	 */
	protected function volatile_get_content_length()
	{
		return $this->headers['Content-Length'];
	}

	/**
	 * Sets the value of the `Date` header field.
	 *
	 * @param mixed $time If 'now' is passed a {@link \Datetime} object is created with the UTC
	 * time zone.
	 */
	protected function volatile_set_date($time)
	{
		if ($time == 'now')
		{
			$time = new \DateTime(null, new \DateTimeZone('UTC'));
		}

		$this->headers['Date'] = $time;
	}

	/**
	 * Returns the value of the `Date` header field.
	 *
	 * @return Headers\DateTime
	 */
	protected function volatile_get_date()
	{
		return $this->headers['Date'];
	}

	/**
	 * Sets the value of the `Age` header field.
	 *
	 * @param int $age
	 */
	protected function volatile_set_age($age)
	{
		$this->headers['Age'] = $age;
	}

	/**
	 * Returns the age of the response.
	 *
	 * @return int
	 */
	protected function volatile_get_age()
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
	protected function volatile_set_last_modified($time)
	{
		$this->headers['Last-Modified'] = $time;
	}

	/**
	 * Returns the value of the `Last-Modified` header field.
	 *
	 * @return Headers\DateTime
	 */
	protected function volatile_get_last_modified()
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
	protected function volatile_set_expires($time)
	{
		$this->headers['Expires'] = $time;

		session_cache_expire($time); // TODO-20120831: Is this required now that we have an awesome response system ?
	}

	/**
	 * Returns the value of the `Expires` header field.
	 *
	 * @return Headers\DateTime
	 */
	protected function volatile_get_expires()
	{
		return $this->headers['Expires'];
	}

	/**
	 * Sets the value of the `Etag` header field.
	 *
	 * @param string $value
	 */
	protected function volatile_set_etag($value)
	{
		$this->headers['Etag'] = $value;
	}

	/**
	 * Returns the value of the `Etag` header field.
	 *
	 * @return string
	 */
	protected function volatile_get_etag()
	{
		return $this->headers['Etag'];
	}

	/**
	 * Sets the directives of the `Cache-Control` header field.
	 *
	 * @param string $cache_directives
	 */
	protected function volatile_set_cache_control($cache_directives)
	{
		$this->headers['Cache-Control'] = $cache_directives;
	}

	/**
	 * Returns the `Cache-Control` header field.
	 *
	 * @return \ICanBoogie\HTTP\Headers\CacheControl
	 */
	protected function volatile_get_cache_control()
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
	protected function volatile_set_ttl($seconds)
	{
		$this->cache_control->s_max_age = $this->age->timestamp + $seconds;
	}

	/**
	 * Returns the response's time-to-live in seconds.
	 *
	 * When the responses TTL is <= 0, the response may not be served from cache without first
	 * revalidating with the origin.
	 *
	 * @return int|null The number of seconds to live, or `null` is no freshness information
	 * is present.
	 */
	protected function volatile_get_ttl()
	{
		$max_age = $this->cache_control->max_age;

		if ($max_age)
		{
			return $max_age - $this->age;
		}
	}

	/**
	 * Set the `cacheable` property of the `Cache-Control` header field to `private`.
	 *
	 * @param boolean $value
	 */
	protected function volatile_set_is_private($value)
	{
		$this->cache_control->cacheable = 'private';
	}

	/**
	 * Checks that the `cacheable` property of the `Cache-Control` header field is `private`.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_private()
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
	protected function volatile_get_is_valid()
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
	protected function volatile_get_is_informational()
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
	protected function volatile_get_is_successful()
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
	protected function volatile_get_is_redirect()
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
	protected function volatile_get_is_client_error()
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
	protected function volatile_get_is_server_error()
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
	protected function volatile_get_is_ok()
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
	protected function volatile_get_is_forbidden()
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
	protected function volatile_get_is_not_found()
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
	protected function volatile_get_is_empty()
	{
		static $range = array(201, 204, 304);

		return in_array($this->status, $range);
	}

	/**
	 * Checks that the response includes header fields that can be used to validate the response
	 * with the origin server using a conditional GET request.
	 *
	 * @return boolean
	 */
	protected function volatile_get_is_validateable()
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
	protected function volatile_get_is_cacheable()
	{
		static $range = array(200, 203, 300, 301, 302, 404, 410);

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
	protected function volatile_get_is_fresh()
	{
		return $this->ttl > 0;
	}

	/**
	 * @throws PropertyNotWritable in attempt to write an unsupported property.
	 *
	 * Check the following:
	 *
	 * - status_message is not writeable.
	 */
	/*
	protected function last_chance_set($property, $value, &$success)
	{
		throw new PropertyNotWritable(array($property, $this));
	}
	*/
}

/**
 * A HTTP response doing a redirect.
 */
class RedirectResponse extends Response
{
	/**
	 * Initializes the `Location` header.
	 *
	 * @param string $url URL to redirect to.
	 * @param int $status Status code (default to 302).
	 * @param array $headers Additional headers.
	 *
	 * @throws \InvalidArgumentException if the provided status code is not a redirect.
	 */
	public function __construct($url, $status=302, array $headers=array())
	{
		parent::__construct
		(
			function($response) {

				$location = $response->location;
				$title = \ICanBoogie\escape($location);

				echo <<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="refresh" content="1;url={$location}" />

	<title>Redirecting to {$title}</title>
</head>
<body>
	Redirecting to <a href="{$location}">{$title}</a>.
</body>
</html>
EOT;
			},

			$status, array('Location' => $url) + $headers
		);

		if (!$this->is_redirect)
		{
			throw new \InvalidArgumentException("The HTTP status code is not a redirect: {$status}.");
		}
	}
}