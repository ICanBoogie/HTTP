<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Dispatcher;

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::alter` event.
 *
 * Third parties may use this event to register additional dispatchers.
 *
 * @property Dispatcher $instance
 */
class AlterEvent extends Event
{
	/**
	 * Reference to the target instance.
	 *
	 * @var Dispatcher
	 */
	private $instance;

	/**
	 * @return Dispatcher
	 */
	protected function get_instance()
	{
		return $this->instance;
	}

	/**
	 * @param Dispatcher $dispatcher
	 */
	protected function set_instance(Dispatcher $dispatcher)
	{
		$this->instance = $dispatcher;
	}

	/**
	 * The event is constructed with the type `alter`.
	 *
	 * @param Dispatcher $target
	 */
	public function __construct(Dispatcher &$target)
	{
		$this->instance = &$target;

		parent::__construct($target, 'alter');
	}
}
