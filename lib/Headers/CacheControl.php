<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Headers;

use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;
use function array_key_exists;
use function array_map;
use function explode;
use function get_object_vars;
use function ICanBoogie\format;
use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;
use function strtr;
use function substr;

/**
 * Representation of the `Cache-Control` header field.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Headers\CacheControl;
 *
 * $cc = CacheControl::from('public, max-age=3600');
 * echo $cc->cacheable;           // true
 * echo $cc->max_age;             // 3600
 *
 * $cc->cacheable = 'no-cache';
 * $cc->max_age = null;
 * $cc->no_store = true;
 * $cc->must_revalidate = true;
 * echo $cc;                      // no-cache, no-store, must-revalidate
 * </pre>
 *
 * @property bool $cacheable
 *
 * @see http://tools.ietf.org/html/rfc2616#section-14.9
 */
class CacheControl
{
	/**
	 * @uses get_cacheable
	 * @uses set_cacheable
	 * @uses get_default_values
	 */
	use AccessorTrait;

	private const CACHEABLE_VALUES = [

		'private',
		'public',
		'no-cache'

	];

	private const BOOLEANS = [

		'no-store',
		'no-transform',
		'only-if-cached',
		'must-revalidate',
		'proxy-revalidate'

	];

	private const PLACEHOLDER = [

		'cacheable'

	];

	/**
	 * Returns the default values of the instance.
	 */
	private static function get_default_values(): array
	{
		return [

			'no_store' => false,
			'max_age' => null,
			's_maxage' => null,
			'max_stale' => null,
			'min_fresh' => null,
			'no_transform' => false,
			'only_if_cached' => false,
			'must_revalidate' => false,
			'proxy_revalidate' => false,
			'extensions' => []

		];
	}

	/**
	 * Parses the provided cache directive.
	 *
	 * @return array Returns an array made of the properties and extensions.
	 */
	static protected function parse(string $cache_directive): array
	{
		$directives = explode(',', $cache_directive);
		$directives = array_map('trim', $directives);

		$properties = self::get_default_values();
		$extensions = [];

		foreach ($directives as $value)
		{
			if (in_array($value, self::BOOLEANS))
			{
				$property = strtr($value, '-', '_');
				$properties[$property] = true;
			}
			if (in_array($value, self::CACHEABLE_VALUES))
			{
				$properties['cacheable'] = $value;
			}
			else if (preg_match('#^([^=]+)=(.+)$#', $value, $matches))
			{
				list(, $directive, $value) = $matches;

				$property = strtr($directive, '-', '_');

				if (is_numeric($value))
				{
					$value = 0 + $value;
				}

				if (!array_key_exists($property, $properties))
				{
					$extensions[$property] = $value;

					continue;
				}

				$properties[$property] = $value;
			}
		}

		return [ $properties, $extensions ];
	}

	/**
	 * Create an instance from the provided source.
	 *
	 * @param self|string $source
	 */
	static public function from($source): self
	{
		if ($source instanceof self)
		{
			return $source;
		}

		return new static($source);
	}

	/**
	 * Whether the request/response is cacheable. The following properties are supported: `public`,
	 * `private` and `no-cache`. The variable may be empty in which case the cacheability of the
	 * request/response is unspecified.
	 *
	 * Scope: request, response.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.1
	 *
	 * @var string|null
	 */
	private $cacheable;

	private function get_cacheable(): ?string
	{
		return $this->cacheable;
	}

	/**
	 * @param string|false|null $value
	 */
	private function set_cacheable($value)
	{
		if ($value === false)
		{
			$value = 'no-cache';
		}

		if ($value !== null && !in_array($value, self::CACHEABLE_VALUES))
		{
			throw new InvalidArgumentException(format
			(
				"%var must be one of: public, private, no-cache. Give: %value", [

					'var' => 'cacheable',
					'value' => $value

				]
			));
		}

		$this->cacheable = $value;
	}

	/**
	 * Whether the request/response is can be stored.
	 *
	 * Scope: request, response.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.2
	 *
	 * @var bool
	 */
	public $no_store = false;

	/**
	 * Indicates that the client is willing to accept a response whose age is no greater than the
	 * specified time in seconds. Unless `max-stale` directive is also included, the client is not
	 * willing to accept a stale response.
	 *
	 * Scope: request.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.3
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4
	 *
	 * @var int|null
	 */
	public $max_age;

	/**
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.3
	 *
	 * @var int|null
	 */
	public $s_maxage;

	/**
	 * Indicates that the client is willing to accept a response that has exceeded its expiration
	 * time. If max-stale is assigned a value, then the client is willing to accept a response
	 * that has exceeded its expiration time by no more than the specified number of seconds. If
	 * no value is assigned to max-stale, then the client is willing to accept a stale response
	 * of any age.
	 *
	 * Scope: request.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.3
	 *
	 * @var string|null
	 */
	public $max_stale;

	/**
	 * Indicates that the client is willing to accept a response whose freshness lifetime is no
	 * less than its current age plus the specified time in seconds. That is, the client wants a
	 * response that will still be fresh for at least the specified number of seconds.
	 *
	 * Scope: request.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.3
	 *
	 * @var int|null
	 */
	public $min_fresh;

	/**
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.5
	 *
	 * Scope: request, response.
	 *
	 * @var bool
	 */
	public $no_transform = false;

	/**
	 * Scope: request.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4
	 *
	 * @var bool
	 */
	public $only_if_cached = false;

	/**
	 * Scope: response.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4
	 *
	 * @var bool
	 */
	public $must_revalidate = false;

	/**
	 * Scope: response.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.4
	 *
	 * @var bool
	 */
	public $proxy_revalidate = false;

	/**
	 * Scope: request, response.
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.6
	 *
	 * @var array
	 */
	public $extensions = [];

	/**
	 * If they are defined, the object is initialized with the cache directives.
	 *
	 * @param string|null $cache_directives Cache directives.
	 */
	public function __construct(string $cache_directives = null)
	{
		if ($cache_directives)
		{
			$this->modify($cache_directives);
		}
	}

	/**
	 * Returns cache directives.
	 */
	public function __toString(): string
	{
		$cache_directive = '';

		foreach (get_object_vars($this) as $directive => $value)
		{
			$directive = strtr($directive, '_', '-');

			if (in_array($directive, self::BOOLEANS))
			{
				if (!$value)
				{
					continue;
				}

				$cache_directive .= ', ' . $directive;
			}
			else if (in_array($directive, self::PLACEHOLDER))
			{
				if (!$value)
				{
					continue;
				}

				$cache_directive .= ', ' . $value;
			}
			else if (is_array($value))
			{
				// TODO: 20120831: extentions

				continue;
			}
			else if ($value !== null && $value !== false)
			{
				$cache_directive .= ", $directive=$value";
			}
		}

		return $cache_directive ? substr($cache_directive, 2) : '';
	}

	/**
	 * Sets the cache directives, updating the properties of the object.
	 *
	 * Unknown directives are stashed in the {@link $extensions} property.
	 */
	public function modify(string $cache_directive): void
	{
		[ $properties, $extensions ] = static::parse($cache_directive);

		foreach ($properties as $property => $value)
		{
			$this->$property = $value;
		}

		$this->extensions = $extensions;
	}
}
