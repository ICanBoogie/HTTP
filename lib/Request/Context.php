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

use ICanBoogie\HTTP\DispatcherInterface;
use ICanBoogie\HTTP\Request;

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 *
 * @property-read Request $request The request associated with the context.
 * @property-read DispatcherInterface $dispatcher The dispatcher currently dispatching the request.
 */
class Context extends \ICanBoogie\Object
{
	/**
	 * The request the context belongs to.
	 *
	 * The variable is declared as private but is actually readable thanks to the
	 * {@link get_request} getter.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * The dispatcher currently dispatching the request.
	 *
	 * @var DispatcherInterface|null
	 */
	private $dispatcher;

	/**
	 * Constructor.
	 *
	 * @param Request $request The request the context belongs to.
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Returns the {@link $request} property.
	 *
	 * @return Request
	 */
	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * Sets the dispatcher currently dispatching the request.
	 *
	 * @param DispatcherInterface|null $dispatcher
	 *
	 * @throws \InvalidArgumentException if the value is not null and does not implements {@link DispatcherInterface}.
	 */
	protected function set_dispatcher($dispatcher)
	{
		if ($dispatcher !== null && !($dispatcher instanceof DispatcherInterface))
		{
			throw new \InvalidArgumentException('$dispatcher must be an instance of ICanBoogie\HTTP\DispatcherInterface. Given: ' . get_class($dispatcher) . '.');
		}

		$this->dispatcher = $dispatcher;
	}

	/**
	 * Returns the {@link $dispatcher} property.
	 *
	 * @return DispatcherInterface
	 */
	protected function get_dispatcher()
	{
		return $this->dispatcher;
	}
}
