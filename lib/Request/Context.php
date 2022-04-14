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
class Context
{
    /**
     * @var object[]
     */
    private array $values = [];

    public function __construct(Request $request)
    {
        $this->add($request);
    }

    public function add(object $value): void
    {
        array_unshift($this->values, $value);
    }

    /**
     * @param class-string $class
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
     * @param class-string $class
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
