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

use ArrayAccess;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\PrototypeTrait;
use RuntimeException;

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 *
 * @property-read Request $request The request associated with the context.
 * @property Dispatcher|null $dispatcher The dispatcher currently dispatching the request.
 */
class Context implements ArrayAccess
{
    /**
     * @uses get_request
     * @uses get_dispatcher
     * @uses set_dispatcher
     */
    use PrototypeTrait;

    /**
     * @var object[]
     */
    private array $values = [];

    private function get_request(): Request
    {
        return $this->request;
    }

    /**
     * The dispatcher currently dispatching the request.
     */
    private ?Dispatcher $dispatcher = null;

    private function set_dispatcher(?Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    private function get_dispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    public function __construct(
        private readonly Request $request
    ) {
    }

    public function add(object $value): void
    {
        array_unshift($this->values, $value);
    }

    /**
     * @param class-string $class
     *
     * @return object
     */
    public function get(string $class): object
    {
        $value = $this->find($class);

        if (!$value) {
            throw new RuntimeException("Unable to find value matching: $class.");
        }

        return $value;
    }

    public function find(string $class): ?object
    {
        foreach ($this->values as $value) {
            if ($value instanceof $class) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->$offset;

            return true;
        } catch (PropertyNotDefined $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function offsetGet(mixed $offset): bool
    {
        return $this->$offset;
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }
}
