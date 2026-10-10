<?php

namespace ICanBoogie\HTTP\Headers;

use ArrayAccess;
use ICanBoogie\OffsetNotDefined;
use InvalidArgumentException;

use function array_intersect_key;
use function array_shift;
use function preg_match_all;
use function trim;

/**
 * Base class for header fields made of a value and parameters.
 *
 * Child classes declare the parameters they support with the `PARAMETERS` constant, and expose
 * them as typed properties:
 *
 * <pre>
 * <?php
 *
 * namespace ICanBoogie\HTTP\Headers;
 *
 * class ContentDisposition extends Header
 * {
 *     protected const array PARAMETERS = [ 'filename' ];
 *
 *     public ?string $type {
 *         get => $this->value;
 *         set { $this->value = $value; }
 *     }
 *
 *     public ?string $filename {
 *         get => $this->parameters['filename']->value;
 *         set { $this->set_parameter('filename', $value); }
 *     }
 * }
 * </pre>
 *
 * The instance of a parameter itself, with its language and charset, is accessed using the header
 * as an array:
 *
 * <pre>
 * <?php
 *
 * $cd = new ContentDisposition;
 * $cd['filename']->value = "Statistics.csv";
 * $cd['filename']->language = "en";
 * </pre>
 *
 * Unrecognized parameters are ignored, to enable future extensions.
 *
 * @implements ArrayAccess<string, HeaderParameter>
 */
abstract class Header implements ArrayAccess
{
    /**
     * The names of the parameters supported by the header.
     *
     * @var string[]
     */
    protected const array PARAMETERS = [];

    /**
     * The value of the header.
     */
    public ?string $value;

    /**
     * The parameters supported by the header.
     *
     * @var HeaderParameter[]
     */
    protected array $parameters = [];

    /**
     * Creates a {@see Header} instance from the provided source.
     *
     * @param string|Header|null $source The source to create the instance from. If the source is
     * an instance of {@see Header} it is returned as is.
     */
    public static function from(string|self|null $source): static
    {
        if ($source instanceof static) {
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
        // Splits on `;`, except within quoted strings, such as `filename="a;b.txt"`.
        preg_match_all('/(?:[^;"]++|"(?:[^"\\\\]++|\\\\.)*+"?)++/s', $source, $matches);

        $segments = $matches[0];
        $value = $source === '' || $source[0] === ';' ? '' : array_shift($segments);
        $parameters = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            try {
                $parameter = HeaderParameter::from($segment);
            } catch (InvalidArgumentException) {
                // Malformed parameters are ignored, they usually come from the client.
                continue;
            }

            $parameters[$parameter->attribute] = $parameter;
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
     * If the value is an instance of {@see HeaderParameter} then the parameter is replaced,
     * otherwise the value of the current parameter is updated, and its language is set to `null`.
     *
     * @param string $offset An attribute.
     * @param mixed $value
     *
     * @throws OffsetNotDefined in an attempt to access a parameter that is not defined.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined($offset, $this);
        }

        if ($value instanceof HeaderParameter) {
            $this->parameters[$offset] = $value;
        } else {
            $this->parameters[$offset]->value = $value;
            $this->parameters[$offset]->language = null;
        }
    }

    /**
     * Returns a {@see HeaderParameter} instance.
     *
     * @param string $offset An attribute.
     *
     * @return HeaderParameter
     *
     * @throws OffsetNotDefined in an attempt to access a parameter that is not defined.
     */
    public function offsetGet(mixed $offset): HeaderParameter
    {
        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined($offset, $this);
        }

        return $this->parameters[$offset];
    }

    /**
     * @param array<string, string|null> $attributes The values of the parameters. Unsupported
     * parameters are ignored.
     */
    public function __construct(?string $value = null, array $attributes = [])
    {
        $this->value = $value;

        foreach (static::PARAMETERS as $attribute) {
            $this->parameters[$attribute] = new HeaderParameter($attribute);
        }

        foreach (array_intersect_key($attributes, $this->parameters) as $attribute => $value) {
            $this[$attribute] = $value;
        }
    }

    /**
     * Sets the value of a parameter, and resets its language.
     */
    protected function set_parameter(string $attribute, ?string $value): void
    {
        $this[$attribute] = $value;
    }

    /**
     * Renders the instance's value and parameters into a string.
     */
    public function __toString(): string
    {
        $value = $this->value;

        if ($value === null || $value === '') {
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
