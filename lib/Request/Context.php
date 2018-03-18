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
	use PrototypeTrait;

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

	/**
	 * @inheritdoc
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
	 */
	public function offsetGet($property)
	{
		return $this->$property;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetSet($property, $value)
	{
		$this->$property = $value;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetUnset($property)
	{
		unset($this->$property);
	}
}
