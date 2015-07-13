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
use ICanBoogie\HTTP\WeightedDispatcher;

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

	/**
	 * Inserts a dispatcher.
	 *
	 * @param string $id Dispatcher identifier.
	 * @param mixed $dispatcher Dispatcher.
	 * @param int $weight
	 */
	public function insert($id, $dispatcher, $weight = 0)
	{
		$this->instance[$id] = new WeightedDispatcher($dispatcher, $weight);
	}

	/**
	 * Inserts a dispatcher before another.
	 *
	 * @param string $id Dispatcher identifier.
	 * @param mixed $dispatcher Dispatcher.
	 * @param string $reference Reference dispatcher identifier.
	 */
	public function insert_before($id, $dispatcher, $reference)
	{
		$this->insert($id, $dispatcher, WeightedDispatcher::WEIGHT_BEFORE_PREFIX . $reference);
	}

	/**
	 * Inserts a dispatcher after another.
	 *
	 * @param string $id Dispatcher identifier.
	 * @param mixed $dispatcher Dispatcher.
	 * @param string $reference Reference dispatcher identifier.
	 */
	public function insert_after($id, $dispatcher, $reference)
	{
		$this->insert($id, $dispatcher, WeightedDispatcher::WEIGHT_AFTER_PREFIX . $reference);
	}
}
