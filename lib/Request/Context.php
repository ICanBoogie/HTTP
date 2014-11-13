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

/**
 * The context of a request.
 *
 * This is a general purpose container used to store the objects and variables related to a
 * request.
 *
 * @property-read \ICanBoogie\HTTP\Request $request The request associated with the context.
 * @property-read \ICanBoogie\HTTP\DispatcherInterface $dispatcher The dispatcher currently dispatching the request.
 */
class Context extends \ICanBoogie\Object
{
	/**
	 * The request the context belongs to.
	 *
	 * The variable is declared as private but is actually readable thanks to the
	 * {@link get_request} getter.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	private $request;

	/**
	 * The dispatcher currently dispatching the request.
	 *
	 * @var \ICanBoogie\HTTP\DispatcherInterface|null
	 */
	private $dispatcher;

	/**
	 * Constructor.
	 *
	 * @param \ICanBoogie\HTTP\Request $request The request the context belongs to.
	 */
	public function __construct(\ICanBoogie\HTTP\Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Returns the {@link $request} property.
	 *
	 * @return \ICanBoogie\HTTP\Request
	 */
	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * Sets the dispatcher currently dispatching the request.
	 *
	 * @param \ICanBoogie\HTTP\DispatcherInterface|null $dispatcher
	 *
	 * @throws \InvalidArgumentException if the value is not null and does not implements \ICanBoogie\HTTP\DispatcherInterface.
	 */
	protected function set_dispatcher($dispatcher)
	{
		if ($dispatcher !== null && !($dispatcher instanceof \ICanBoogie\HTTP\DispatcherInterface))
		{
			throw new \InvalidArgumentException('$dispatcher must be an instance of ICanBoogie\HTTP\DispatcherInterface. Given: ' . get_class($dispatcher) . '.');
		}

		$this->dispatcher = $dispatcher;
	}

	/**
	 * Returns the {@link $dispatcher} property.
	 *
	 * @return \ICanBoogie\HTTP\DispatcherInterface
	 */
	protected function get_dispatcher()
	{
		return $this->dispatcher;
	}
}
