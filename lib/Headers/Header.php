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

use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;

/**
 * Base class for header fields.
 *
 * Classes that extend the class and support attributes must defined them during construct:
 *
 * <pre>
 * <?php
 *
 * namespace ICanBoogie\HTTP\Headers;
 *
 * class ContentDisposition extends Header
 * {
 *     public function __construct($value=null, array $attributes=[])
 *     {
 *         $this->parameters['filename'] = new HeaderParameter('filename');
 *
 *         parent::__construct($value, $attributes);
 *     }
 * }
 * </pre>
 *
 * Magic properties are automatically mapped to parameters. The value of a parameter is accessed
 * through its corresponding property:
 *
 * <pre>
 * <?php
 *
 * $cd = new ContentDisposition;
 * $cd->filename = "Statistics.csv";
 * echo $cd->filename;
 * // "Statistics.csv"
 * </pre>
 *
 * The instance of the parameter itself is accessed using the header as an array:
 *
 * <pre>
 * <?php
 *
 * $cd = new ContentDisposition;
 * $cd['filename']->value = "Statistics.csv";
 * $cd['filename']->language = "en";
 * </pre>
 *
 * An alias to the {@link $value} property can be defined by using the `VALUE_ALIAS` constant. The
 * following code defines `type` as an alias:
 *
 * <pre>
 * <?php
 *
 * class ContentDisposition extends Header
 * {
 *     const VALUE_ALIAS = 'type';
 * }
 * </pre>
 */
abstract class Header implements \ArrayAccess
{
	const VALUE_ALIAS = null;

	/**
	 * The value of the header.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The parameters supported by the header.
	 *
	 * @var HeaderParameter[]
	 */
	protected $parameters = [];

	/**
	 * Creates a {@link Header} instance from the provided source.
	 *
	 * @param string|Header $source The source to create the instance from. If the source is
	 * an instance of {@link Header} it is returned as is.
	 *
	 * @return Header
	 */
	static public function from($source)
	{
		if ($source instanceof self)
		{
			return $source;
		}

		if ($source === null)
		{
			return new static;
		}

		list($value, $parameters) = static::parse($source);

		return new static($value, $parameters);
	}

	/**
	 * Parse the provided source and extract its value and parameters.
	 *
	 * @param string|Header $source The source to create the instance from. If the source is
	 * an instance of {@link Header} its value and parameters are returned.
	 *
	 * @throws \InvalidArgumentException if `$source` is not a string nor an object implementing
	 * `__toString()`, or and instance of {@link Header}.
	 *
	 * @return array
	 */
	static protected function parse($source)
	{
		if ($source instanceof self)
		{
			return [ $source->value, $source->parameters ];
		}

		if (is_object($source) && method_exists($source, '__toString'))
		{
			$source = (string) $source;
		}

		if (!is_string($source))
		{
			throw new \InvalidArgumentException(\ICanBoogie\format
			(
				"%var must be a string or an object implementing __toString(). Given: !data", [

					'var' => 'source',
					'data' => $source

				]
			));
		}

		$value_end = strpos($source, ';');
		$parameters = [];

		if ($value_end !== false)
		{
			$value = substr($source, 0, $value_end);
			$attributes = trim(substr($source, $value_end + 1));

			if ($attributes)
			{
				$a = explode(';', $attributes);
				$a = array_map('trim', $a);

				foreach ($a as $attribute)
				{
					$parameter = HeaderParameter::from($attribute);
					$parameters[$parameter->attribute] = $parameter;
				}
			}
		}
		else
		{
			$value = $source;
		}

		return [ $value, $parameters ];
	}

	/**
	 * Checks if a parameter exists.
	 *
	 * @param string $attribute
	 *
	 * @return bool
	 */
	public function offsetExists($attribute)
	{
		return isset($this->parameters[$attribute]);
	}

	/**
	 * Sets the value of a parameter to `null`.
	 *
	 * @param string $attribute
	 */
	public function offsetUnset($attribute)
	{
		$this->parameters[$attribute]->value = null;
	}

	/**
	 * Sets the value of a parameter.
	 *
	 * If the value is an instance of {@link HeaderParameter} then the parameter is replaced,
	 * otherwise the value of the current parameter is updated and its language is set to `null`.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 *
	 * @throws OffsetNotDefined in attempt to access a parameter that is not defined.
	 */
	public function offsetSet($attribute, $value)
	{
		if (!$this->offsetExists($attribute))
		{
			throw new OffsetNotDefined([ $attribute, $this ]);
		}

		if ($value instanceof HeaderParameter)
		{
			$this->parameters[$attribute] = $value;
		}
		else
		{
			$this->parameters[$attribute]->value = $value;
			$this->parameters[$attribute]->language = null;
		}
	}

	/**
	 * Returns a {@link HeaderParameter} instance.
	 *
	 * @param string $attribute
	 *
	 * @return HeaderParameter
	 *
	 * @throws OffsetNotDefined in attempt to access a parameter that is not defined.
	 */
	public function offsetGet($attribute)
	{
		if (!$this->offsetExists($attribute))
		{
			throw new OffsetNotDefined([ $attribute, $this ]);
		}

		return $this->parameters[$attribute];
	}

	/**
	 * Initializes the {@link $name}, {@link $value} and {@link $parameters} properties.
	 *
	 * To enable future extensions, unrecognized parameters are ignored. Supported parameters must
	 * be defined by a child class before it calls its parent.
	 *
	 * @param string $value
	 * @param array $parameters
	 */
	public function __construct($value=null, array $parameters=[])
	{
		$this->value = $value;

		$parameters = array_intersect_key($parameters, $this->parameters);

		foreach ($parameters as $attribute => $value)
		{
			$this[$attribute] = $value;
		}
	}

	/**
	 * Returns the value of a defined parameter.
	 *
	 * The method also handles the alias of the {@link $value} property.
	 *
	 * @param string $property
	 *
	 * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		if ($property === static::VALUE_ALIAS)
		{
			return $this->value;
		}

		if ($this->offsetExists($property))
		{
			return $this[$property]->value;
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}

	/**
	 * Sets the value of a supported parameter.
	 *
	 * The method also handles the alias of the {@link $value} property.
	 *
	 * @param string $property
	 * @param mixed $value
	 *
	 * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
	 */
	public function __set($property, $value)
	{
		if ($property === static::VALUE_ALIAS)
		{
			$this->value = $value;

			return;
		}

		if ($this->offsetExists($property))
		{
			$this[$property]->value = $value;

			return;
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}

	/**
	 * Unsets the matching parameter.
	 *
	 * @param string $property
	 *
	 * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
	 */
	public function __unset($property)
	{
		if (isset($this->parameters[$property]))
		{
			unset($this[$property]);

			return;
		}

		throw new PropertyNotDefined([ $property, $this ]);
	}

	/**
	 * Renders the instance's value and parameters into a string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$value = $this->value;

		if (!$value && $value !== 0)
		{
			return '';
		}

		foreach ($this->parameters as $attribute)
		{
			$rendered_attribute = $attribute->render();

			if (!$rendered_attribute)
			{
				continue;
			}

			$value .= '; ' . $rendered_attribute;
		}

		return $value;
	}
}
