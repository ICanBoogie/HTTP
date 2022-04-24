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

use ArrayAccess;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use InvalidArgumentException;

use function array_intersect_key;
use function array_map;
use function explode;
use function strpos;
use function substr;
use function trim;

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
 *
 * @implements ArrayAccess<string, mixed>
 */
abstract class Header implements ArrayAccess
{
    public const VALUE_ALIAS = null;

    /**
     * The value of the header.
     */
    public mixed $value;

    /**
     * The parameters supported by the header.
     *
     * @var HeaderParameter[]
     */
    protected array $parameters = [];

    /**
     * Creates a {@link Header} instance from the provided source.
     *
     * @param string|Header|null $source The source to create the instance from. If the source is
     * an instance of {@link Header} it is returned as is.
     */
    public static function from(string|self|null $source): Header
    {
        if ($source instanceof self) {
            return $source;
        }

        if ($source === null) {
            return new static(); // @phpstan-ignore-line
        }

        return new static(...static::parse($source)); // @phpstan-ignore-line
    }

    /**
     * Parse the provided source and extract its value and parameters.
     *
     * @throws InvalidArgumentException if `$source` is not a string nor an object implementing
     *     `__toString()`.
     *
     * @phpstan-return array{ 0: string, 1: array<string, mixed> }
     */
    protected static function parse(string $source): array
    {
        $value_end = strpos($source, ';');
        $parameters = [];

        if ($value_end !== false) {
            $value = substr($source, 0, $value_end);
            $attributes = trim(substr($source, $value_end + 1));

            if ($attributes) {
                $attributes = explode(';', $attributes);
                $attributes = array_map('trim', $attributes);

                foreach ($attributes as $attribute) {
                    $parameter = HeaderParameter::from($attribute);
                    $parameters[$parameter->attribute] = $parameter;
                }
            }
        } else {
            $value = $source;
        }

        return [ $value, $parameters ];
    }

    /**
     * Checks if a parameter exists.
     *
     * @param string $offset An attribute.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->parameters[$offset]);
    }

    /**
     * Sets the value of a parameter to `null`.
     *
     * @param string $offset An attribute.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->parameters[$offset]->value = null;
    }

    /**
     * Sets the value of a parameter.
     *
     * If the value is an instance of {@link HeaderParameter} then the parameter is replaced,
     * otherwise the value of the current parameter is updated and its language is set to `null`.
     *
     * @param string $offset An attribute.
     * @param mixed $value
     *
     * @throws OffsetNotDefined in attempt to access a parameter that is not defined.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined([ $offset, $this ]);
        }

        if ($value instanceof HeaderParameter) {
            $this->parameters[$offset] = $value;
        } else {
            $this->parameters[$offset]->value = $value;
            $this->parameters[$offset]->language = null;
        }
    }

    /**
     * Returns a {@link HeaderParameter} instance.
     *
     * @param string $offset An attribute.
     *
     * @return HeaderParameter
     *
     * @throws OffsetNotDefined in attempt to access a parameter that is not defined.
     */
    public function offsetGet(mixed $offset): HeaderParameter
    {
        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined([ $offset, $this ]);
        }

        return $this->parameters[$offset];
    }

    /**
     * Initializes the {@link $name}, {@link $value} and {@link $parameters} properties.
     *
     * To enable future extensions, unrecognized parameters are ignored. Supported parameters must
     * be defined by a child class before it calls its parent.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(mixed $value = null, array $attributes = [])
    {
        $this->value = $value;

        $attributes = array_intersect_key($attributes, $this->parameters);

        foreach ($attributes as $attribute => $value) {
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
     * @return mixed
     *
     * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
     */
    public function __get(string $property)
    {
        if ($property === static::VALUE_ALIAS) {
            return $this->value;
        }

        if ($this->offsetExists($property)) {
            return $this[$property]->value;
        }

        throw new PropertyNotDefined([ $property, $this ]);
    }

    /**
     * Sets the value of a supported parameter.
     *
     * The method also handles the alias of the {@link $value} property.
     *
     * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
     */
    public function __set(string $property, mixed $value): void
    {
        if ($property === static::VALUE_ALIAS) {
            $this->value = $value;

            return;
        }

        if ($this->offsetExists($property)) {
            $this[$property]->value = $value;

            return;
        }

        throw new PropertyNotDefined([ $property, $this ]);
    }

    /**
     * Unsets the matching parameter.
     *
     * @throws PropertyNotDefined in attempt to access a parameter that is not defined.
     */
    public function __unset(string $property): void
    {
        if (!isset($this->parameters[$property])) {
            return;
        }

        unset($this[$property]);
    }

    /**
     * Renders the instance's value and parameters into a string.
     */
    public function __toString(): string
    {
        $value = $this->value;

        if (!$value && $value !== 0) {
            return '';
        }

        foreach ($this->parameters as $attribute) {
            $rendered_attribute = $attribute->render();

            if (!$rendered_attribute) {
                continue;
            }

            $value .= '; ' . $rendered_attribute;
        }

        return $value;
    }
}
