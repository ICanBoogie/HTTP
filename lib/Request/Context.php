<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Request;

use ICanBoogie\HTTP\Request;
use RuntimeException;

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 */
final class Context
{
    /**
     * @var object[]
     */
    private array $values = [];

    public function __construct(
        public readonly Request $request
    ) {
    }

    /**
     * Add an object to the top of the context.
     *
     * Multiple objects of the same type can be added.
     */
    public function add(object $value): void
    {
        array_unshift($this->values, $value);
    }

    /**
     * Get an object from the context.
     *
     * The method will fail if there's no object matching the specified class or interface.
     *
     * @template T of object
     *
     * @param class-string<T> $class A class or interface.
     *
     * @return T
     */
    public function get(string $class): object
    {
        $value = $this->find($class);

        if (!$value) {
            throw new RuntimeException("Unable to find value matching: $class.");
        }

        return $value;
    }

    /**
     * Find an object in the context.
     *
     * @template T of object
     *
     * @param class-string<T> $class A class or interface.
     *
     * @return T|null
     */
    public function find(string $class): ?object
    {
        foreach ($this->values as $value) {
            if ($value instanceof $class) {
                return $value;
            }
        }

        return null;
    }
}
