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
	private const MAPPING = [

		'Cache-Control'       => Headers\CacheControl::class,
		'Content-Disposition' => Headers\ContentDisposition::class,
		'Content-Type'        => Headers\ContentType::class,
		'Date'                => Headers\Date::class,
		'Expires'             => Headers\Date::class,
		'If-Modified-Since'   => Headers\Date::class,
		'If-Unmodified-Since' => Headers\Date::class,
		'Last-Modified'       => Headers\Date::class

	];

	/**
	 * Normalizes field name.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	static private function normalize_field_name(string $name): string
	{
		return \mb_convert_case(\strtr(\substr($name, 5), '_', '-'), MB_CASE_TITLE);
	}

	/**
	 * Header fields.
	 *
	 * @var array
	 */
	private $fields = [];

	/**
	 * If the `REQUEST_URI` key is found in the header fields they are considered coming from the
	 * super global `$_SERVER` array in which case they are filtered to keep only keys
	 * starting with the `HTTP_` prefix. Also, header field names are normalized. For instance,
	 * `HTTP_CONTENT_TYPE` becomes `Content-Type`.
	 *
	 * @param array $fields The initial headers.
	 */
	public function __construct(array $fields = [])
	{
		if (isset($fields['REQUEST_URI']))
		{
			foreach ($fields as $field => $value)
			{
				if (\strpos($field, 'HTTP_') !== 0)
				{
					continue;
				}

				$field = self::normalize_field_name($field);

				$this[$field] = $value;
			}
		}
		else
		{
			foreach ($fields as $field => $value)
			{
				if (\strpos($field, 'HTTP_') === 0)
				{
					$field = self::normalize_field_name($field);
				}

				$this[$field] = $value;
			}
		}
	}

	/**
	 * Clone instantiated fields.
	 */
	public function __clone()
	{
		foreach ($this->fields as &$field)
		{
			if (!\is_object($field))
			{
				continue;
			}

			$field = clone $field;
		}
	}

	/**
	 * Returns the header as a string.
	 *
	 * Header fields with empty string values are discarded.
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
	 * Header fields with empty string values are discarded.
	 */
	public function __invoke(): void
	{
		foreach ($this->fields as $field => $value)
		{
			$value = (string) $value;

			if ($value === '')
			{
				continue;
			}

			$this->send_header($field, $value);
		}
	}

	/**
	 * Send header field.
	 *
	 * Note: The only reason for this method is testing.
	 *
	 * @param string $field
	 * @param string $value
	 */
	protected function send_header(string $field, string $value): void // @codeCoverageIgnoreStart
	{
		\header("$field: $value");
	}// @codeCoverageIgnoreEnd

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
		if (isset(self::MAPPING[$field]))
		{
			if (empty($this->fields[$field]))
			{
				/* @var $class Headers\Header|string */
				$class = self::MAPPING[$field];
				$this->fields[$field] = $class::from(null);
			}

			return $this->fields[$field];
		}

		return $this->offsetExists($field) ? $this->fields[$field] : null;
	}

	/**
	 * Sets a header field.
	 *
	 * > **Note:** Setting a header field to `null` removes it, just like unset() would.
	 *
	 * **Date, Expires, Last-Modified**
	 *
	 * The `Date`, `Expires` and `Last-Modified` header fields can be provided as a Unix
	 * timestamp, a string or a {@link \DateTime} object.
	 *
	 * **Cache-Control, Content-Disposition and Content-Type**
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

				if (\is_string($value))
				{
					$pos = \strpos($value, ';');

					if ($pos)
					{
						$value = \substr($value, 0, $pos);
					}
				}
			}
			break;

			# http://tools.ietf.org/html/rfc2616#section-14.37
			case 'Retry-After':
			{
				$value = \is_numeric($value) ? $value : Headers\Date::from($value);
			}
			break;
		}

		if (isset(self::MAPPING[$field]))
		{
			/* @var $class Headers\Header|string */
			$class = self::MAPPING[$field];
			$value = $class::from($value);
		}

		$this->fields[$field] = $value;
	}

	/**
	 * Removes a header field.
	 *
	 * @param mixed $field
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
