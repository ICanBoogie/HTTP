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
use ICanBoogie\DateTime;

use function ICanBoogie\format;

/**
 * A response to a HTTP request.
 *
 * @property Status $status
 * @property mixed $body The body of the response.
 *
 * @property int|null $ttl Adjusts the `s-maxage` part of the `Cache-Control` header field definition according to the `Age` header field definition.
 * @property int|null $age Shortcut to the `Age` header field definition.
 * @property Headers\CacheControl $cache_control Shortcut to the `Cache-Control` header field definition.
 * @property int $content_length Shortcut to the `Content-Length` header field definition.
 * @property Headers\ContentType $content_type Shortcut to the `Content-Type` header field definition.
 * @property Headers\Date $date Shortcut to the `Date` header field definition.
 * @property string|null $etag Shortcut to the `ETag` header field definition.
 * @property Headers\Date $expires Shortcut to the `Expires` header field definition.
 * @property Headers\Date $last_modified Shortcut to the `Last-Modified` header field definition.
 * @property string|null $location Shortcut to the `Location` header field definition.
 *
 * @property-read boolean $is_cacheable {@link get_is_cacheable()}
 * @property-read boolean $is_fresh {@link get_is_fresh()}
 * @property-read boolean $is_validateable {@link get_is_validateable()}
 *
 * @see http://tools.ietf.org/html/rfc2616
 */
class Response implements ResponseStatus
{
	use AccessorTrait;

	/**
	 * Response headers.
	 *
	 * @var Headers
	 */
	public $headers;

	/**
	 * The HTTP protocol version (1.0 or 1.1), defaults to '1.1'
	 *
	 * @var string
	 */
	public $version = '1.1';

	/**
	 * Initializes the `$body`,  `$status`, `$headers`, and `$date` properties.
	 *
	 * @param mixed $body The body of the response.
	 * @param int|Status $status The status code of the response.
	 * @param Headers|array $headers The initial header fields of the response.
	 */
	public function __construct($body = null, $status = self::STATUS_OK, $headers = [])
	{
		if (is_array($headers))
		{
			$headers = new Headers($headers);
		}
		elseif (!$headers instanceof Headers)
		{
			throw new \InvalidArgumentException("\$headers must be an array or a ICanBoogie\\HTTP\\Headers instance. Given: " . gettype($headers));
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
	 * Clones the `$headers` and `$status` properties.
	 */
	public function __clone()
	{
		$this->headers = clone $this->headers;
		$this->status = clone $this->status;
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

			$this->send_body($body);

			$body = ob_get_clean();

			return "HTTP/{$this->version} {$this->status}\r\n"
			. $header
			. "\r\n"
			. $body;
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Issues the HTTP response.
	 *
	 * {@link finalize()} is invoked to finalize the headers (a cloned actually)
	 * and the body. {@link send_headers} is invoked to send the headers and {@link send_body()}
	 *is invoked to send the body, if the body is not `null`.
	 *
	 * The body is not send in the following instances:
	 *
	 * - The finalized body is `null`.
	 * - The status is not ok.
	 */
	public function __invoke()
	{
		$headers = clone $this->headers;
		$body = $this->body;

		$this->finalize($headers, $body);
		$this->send_headers($headers);

		if ($body === null)
		{
			return;
		}

		$this->send_body($body);
	}

	/**
	 * Finalize the body.
	 *
	 * Subclasses might want to override this method if they wish to alter the header or the body
	 * before the response is sent or transformed into a string.
	 *
	 * @param Headers $headers Reference to the final header.
	 * @param mixed $body Reference to the final body.
	 */
	protected function finalize(Headers &$headers, &$body)
	{
		if ($body instanceof \Closure || !method_exists($body, '__toString'))
		{
			return;
		}

		$body = (string) $body;
	}

	/**
	 * Sends response headers.
	 *
	 * @param Headers $headers
	 *
	 * @return bool `true` is the headers were sent, `false` otherwise.
	 */
	protected function send_headers(Headers $headers) 	// @codeCoverageIgnoreStart
	{
		if (headers_sent($file, $line))
		{
			trigger_error(format("Cannot modify header information because it was already sent. Output started at !at.", [

				'at' => $file . ':' . $line

			]));

			return false;
		}

		header_remove('Pragma');
		header_remove('X-Powered-By');

		header("HTTP/{$this->version} {$this->status}");

		$headers();

		return true;
	} // @codeCoverageIgnoreEnd

	/**
	 * Sends response body.
	 *
	 * @param mixed $body
	 */
	protected function send_body($body)
	{
		if ($body instanceof \Closure)
		{
			$body($this);

			return;
		}

		echo $body;
	}

	/**
	 * Status of the HTTP response.
	 *
	 * @var Status
	 */
	private $status;

	/**
	 * Sets response status code and optionally status message.
	 *
	 * @param int|Status $status HTTP status code or HTTP status code and HTTP status message.
	 */
	protected function set_status($status)
	{
		$this->status = Status::from($status);
	}

	/**
	 * Returns the response status.
	 *
	 * @return Status
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
	 * Set the response body.
	 *
	 * The body can be any data type that can be converted into a string. This includes numeric
	 * and objects implementing the {@link __toString()} method.
	 *
	 * @param string|\Closure|null $body
	 *
	 * @throws \UnexpectedValueException when the body cannot be converted to a string.
	 */
	protected function set_body($body)
	{
		$this->assert_body_is_valid($body);

		$this->body = $body;
	}

	/**
	 * Assert that a body is valid.
	 *
	 * @param $body
	 *
	 * @throws \UnexpectedValueException if the specified body doesn't meet the requirements.
	 */
	protected function assert_body_is_valid($body)
	{
		if ($body === null
		|| $body instanceof \Closure
		|| is_numeric($body)
		|| is_string($body)
		|| (is_object($body) && method_exists($body, '__toString')))
		{
			return;
		}

		throw new \UnexpectedValueException(format('The Response body must be a string, an object implementing the __toString() method or be callable, %type given. !value', [

			'type' => gettype($body),
			'value' => $body

		]));
	}

	/**
	 * Returns the response body.
	 *
	 * @return string
	 */
	protected function get_body()
	{
		return $this->body;
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

		if (!$this->date->is_empty)
		{
			return max(0, time() - $this->date->utc->timestamp);
		}

		return null;
	}

	/**
	 * Sets the value of the `Last-Modified` header field.
	 *
	 * @param mixed $time
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
	 * The method updates the `max-age` Cache Control directive accordingly.
	 *
	 * @param mixed $time
	 */
	protected function set_expires($time)
	{
		$this->headers['Expires'] = $time;
		/* @var DateTime $expires */
		$expires = $this->headers['Expires'];
		$this->cache_control->max_age = $expires->is_empty ? null : $expires->timestamp - time();
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
	 * Sets the value of the `ETag` header field.
	 *
	 * @param string|null $value
	 */
	protected function set_etag($value)
	{
		$this->headers['ETag'] = $value;
	}

	/**
	 * Returns the value of the `ETag` header field.
	 *
	 * @return string|null
	 */
	protected function get_etag()
	{
		return $this->headers['ETag'];
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
	 * @return Headers\CacheControl
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
	 * @param int|null $seconds The number of seconds.
	 */
	protected function set_ttl($seconds)
	{
		$this->cache_control->s_maxage = $this->age + $seconds;
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

		return null;
	}

	/**
	 * Whether the response includes header fields that can be used to validate the response
	 * with the origin server using a conditional GET request.
	 *
	 * @return boolean
	 */
	protected function get_is_validateable()
	{
		return !$this->headers['Last-Modified']->is_empty || $this->headers['ETag'];
	}

	/**
	 * Whether the response is worth caching under any circumstance.
	 *
	 * Responses marked _private_ with an explicit `Cache-Control` directive are considered
	 * not cacheable.
	 *
	 * Responses with neither a freshness lifetime (Expires, max-age) nor cache validator
	 * (`Last-Modified`, `ETag`) are considered not cacheable.
	 *
	 * @return boolean
	 */
	protected function get_is_cacheable()
	{
		if (!$this->status->is_cacheable || $this->cache_control->no_store || $this->cache_control->cacheable == 'private')
		{
			return false;
		}

		return $this->is_validateable || $this->is_fresh;
	}

	/**
	 * Whether the response is fresh.
	 *
	 * @return boolean
	 */
	protected function get_is_fresh()
	{
		return $this->ttl > 0;
	}
}
