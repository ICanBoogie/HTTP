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

use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\PrototypeTrait;
use RuntimeException;
use function array_shift;
use function get_class;
use function is_subclass_of;

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 *
 * @property-read Request $request The request associated with the context.
 * @property Dispatcher $dispatcher The dispatcher currently dispatching the request.
 */
class Context implements \ArrayAccess
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

	/**
	 * The request the context belongs to.
	 *
	 * @var Request
	 */
	private $request;

	protected function get_request(): Request
	{
		return $this->request;
	}

	/**
	 * The dispatcher currently dispatching the request.
	 *
	 * @var Dispatcher|null
	 */
	private $dispatcher;

	protected function set_dispatcher(?Dispatcher $dispatcher): void
	{
		$this->dispatcher = $dispatcher;
	}

	protected function get_dispatcher(): ?Dispatcher
	{
		return $this->dispatcher;
	}

	/**
	 * @param Request $request The request the context belongs to.
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function add(object $value): void
	{
		array_unshift($this->values, $value);
	}

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
		foreach ($this->values as $value)
		{
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
	public function offsetExists($property)
	{
		try
		{
			$this->$property;

			return true;
		}
		catch (PropertyNotDefined $e)
		{
			return false;
		}
	}

	/**
	 * @inheritdoc
	 *
	 * @deprecated
	 */
	public function offsetGet($property)
	{
		return $this->$property;
	}

	/**
	 * @inheritdoc
	 *
	 * @deprecated
	 */
	public function offsetSet($property, $value)
	{
		$this->$property = $value;
	}

	/**
	 * @inheritdoc
	 *
	 * @deprecated
	 */
	public function offsetUnset($property)
	{
		unset($this->$property);
	}
}
