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
class Context
{
	use PrototypeTrait;

	/**
	 * The request the context belongs to.
	 *
	 * @var Request
	 */
	private $request;

	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * The dispatcher currently dispatching the request.
	 *
	 * @var Dispatcher|null
	 */
	private $dispatcher;

	/**
	 * Sets the dispatcher currently dispatching the request.
	 *
	 * @param Dispatcher|null $dispatcher
	 *
	 * @throws \InvalidArgumentException if the value is not null and does not implements {@link Dispatcher}.
	 */
	protected function set_dispatcher($dispatcher)
	{
		if ($dispatcher !== null && !($dispatcher instanceof Dispatcher))
		{
			throw new \InvalidArgumentException('$dispatcher must be an instance of ICanBoogie\HTTP\DispatcherInterface. Given: ' . get_class($dispatcher) . '.');
		}

		$this->dispatcher = $dispatcher;
	}

	protected function get_dispatcher()
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
}
