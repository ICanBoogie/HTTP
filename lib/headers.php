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
 * HTTP Header field definitions.
 *
 * Instances of this class are used to collect and manipulate HTTP header field definitions.
 * Header field instances are used to handle the definition of complex header fields such as
 * `Content-Type` and `Cache-Control`. For instance a {@link Headers\CacheControl} instance
 * is used to handle the directives of the `Cache-Control` header field.
 *
 * @see http://tools.ietf.org/html/rfc2616#section-14
 */
class Headers implements \ArrayAccess, \IteratorAggregate
{
	static private $mapping = array
	(
		'Cache-Control'       => 'ICanBoogie\HTTP\CacheControlHeader',
		'Content-Disposition' => 'ICanBoogie\HTTP\ContentDispositionHeader',
		'Content-Type'        => 'ICanBoogie\HTTP\ContentTypeHeader',
		'Date'                => 'ICanBoogie\HTTP\DateHeader',
		'Expires'             => 'ICanBoogie\HTTP\DateHeader',
		'If-Modified-Since'   => 'ICanBoogie\HTTP\DateHeader',
		'If-Unmodified-Since' => 'ICanBoogie\HTTP\DateHeader',
		'Last-Modified'       => 'ICanBoogie\HTTP\DateHeader'
	);

	/**
	 * Header fields.
	 *
	 * @var array[string]mixed
	 */
	protected $fields = array();

	/**
	 * If the `REQUEST_URI` key is found in the header fields they are considered coming from the
	 * super global `$_SERVER` array in which case they are filtered to keep only keys
	 * starting with the `HTTP_` prefix. Also, header field names are normalized. For instance,
	 * `HTTP_CONTENT_TYPE` becomes `Content-Type`.
	 *
	 * @param array $headers The initial headers.
	 */
	public function __construct(array $fields=array())
	{
		if (isset($fields['REQUEST_URI']))
		{
			foreach ($fields as $field => $value)
			{
				if (strpos($field, 'HTTP_') !== 0)
				{
					continue;
				}

				$field = strtr(substr($field, 5), '_', '-');
				$field = mb_convert_case($field, MB_CASE_TITLE);
				$this[$field] = $value;
			}
		}
		else
		{
			foreach ($fields as $field => $value)
			{
				if (strpos($field, 'HTTP_') === 0)
				{
					$field = strtr(substr($field, 5), '_', '-');
					$field = mb_convert_case($field, MB_CASE_TITLE);
				}

				$this[$field] = $value;
			}
		}
	}

	/**
	 * Returns the header as a string.
	 *
	 * Header fields with empty string values are discarted.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$header = '';

		foreach ($this->fields as $field => $value)
		{
			$value = (string) $value;

			if ($value === '')
			{
				continue;
			}

			$header .= "$field: $value\r\n";
		}

		return $header;
	}

	/**
	 * Sends header fields using the {@link header()} function.
	 *
	 * Header fields with empty string values are discarted.
	 */
	public function __invoke()
	{
		foreach ($this->fields as $field => $value)
		{
			$value = (string) $value;

			if ($value === '')
			{
				continue;
			}

			header("$field: $value");
		}
	}

	/**
	 * Checks if a header field exists.
	 *
	 * @param mixed $field
	 *
	 * @return boolean
	 */
	public function offsetExists($field)
	{
		return isset($this->fields[(string) $field]);
	}

	/**
	 * Returns a header.
	 *
	 * @param mixed $field
	 *
	 * @return string|null The header field value or null if it is not defined.
	 */
	public function offsetGet($field)
	{
		if (isset(self::$mapping[$field]))
		{
			if (empty($this->fields[$field]))
			{
				$class = self::$mapping[$field];
				$this->fields[$field] = new $class;
			}

			return $this->fields[$field];
		}

		return $this->offsetExists($field) ? $this->fields[$field] : null;
	}

	/**
	 * Sets a header field.
	 *
	 * Note: Setting a header field to `null` removes it, just like unset() would.
	 *
	 * ## Date, Expires, Last-Modified
	 *
	 * The `Date`, `Expires` and `Last-Modified` header fields can be provided as a Unix
	 * timestamp, a string or a {@link \DateTime} object.
	 *
	 * ## Cache-Control, Content-Disposition and Content-Type
	 *
	 * Instances of the {@link Headers\CacheControl}, {@link Headers\ContentDisposition} and
	 * {@link Headers\ContentType} are used to handle the values of the `Cache-Control`,
	 * `Content-Disposition` and `Content-Type` header fields.
	 *
	 * @param string $field The header field to set.
	 * @param mixed $value The value of the header field.
	 */
	public function offsetSet($field, $value)
	{
		if ($value === null)
		{
			unset($this[$field]);

			return;
		}

		switch ($field)
		{
			# http://tools.ietf.org/html/rfc2616#section-14.25
			case 'If-Modified-Since':
			{
				#
				# Removes the ";length=xxx" string added by Internet Explorer.
				# http://stackoverflow.com/questions/12626699/if-modified-since-http-header-passed-by-ie9-includes-length
				#

				$pos = strpos($value, ';');

				if ($pos)
				{
					$value = substr($value, 0, $pos);
				}
			}
			break;

			# http://tools.ietf.org/html/rfc2616#section-14.37
			case 'Retry-After':
			{
				$value = is_numeric($value) ? $value : Headers\DateHeader::from($value);
			}
			break;
		}

		if (isset(self::$mapping[$field]))
		{
			$value = call_user_func(self::$mapping[$field] . '::from', $value);
		}

		$this->fields[$field] = $value;
	}

	/**
	 * Removes a header field.
	 */
	public function offsetUnset($field)
	{
		unset($this->fields[$field]);
	}

	/**
	 * Returns an iterator for the header fields.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->fields);
	}
}