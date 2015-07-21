<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\WeightedDispatcher;

/**
 * Event class for the `ICanBoogie\HTTP\RequestDispatcher::alter` event.
 *
 * Event hooks may use this event to register domain dispatchers.
 *
 * @property Dispatcher $instance
 */
class AlterEvent extends Event
{
	/**
	 * Reference to the target instance.
	 *
	 * @var RequestDispatcher
	 */
	private $instance;

	/**
	 * @return RequestDispatcher
	 */
	protected function get_instance()
	{
		return $this->instance;
	}

	/**
	 * @param RequestDispatcher $dispatcher
	 */
	protected function set_instance(RequestDispatcher $dispatcher)
	{
		$this->instance = $dispatcher;
	}

	/**
	 * The event is constructed with the type `alter`.
	 *
	 * @param RequestDispatcher $target
	 */
	public function __construct(RequestDispatcher &$target)
	{
		$this->instance = &$target;

		parent::__construct($target, 'alter');
	}

	/**
	 * Inserts a dispatcher.
	 *
	 * @param string $id RequestDispatcher identifier.
	 * @param mixed $dispatcher RequestDispatcher.
	 * @param int $weight
	 */
	public function insert($id, $dispatcher, $weight = 0)
	{
		$this->instance[$id] = new WeightedDispatcher($dispatcher, $weight);
	}

	/**
	 * Inserts a dispatcher before another.
	 *
	 * @param string $id RequestDispatcher identifier.
	 * @param mixed $dispatcher RequestDispatcher.
	 * @param string $reference Reference dispatcher identifier.
	 */
	public function insert_before($id, $dispatcher, $reference)
	{
		$this->insert($id, $dispatcher, WeightedDispatcher::WEIGHT_BEFORE_PREFIX . $reference);
	}

	/**
	 * Inserts a dispatcher after another.
	 *
	 * @param string $id RequestDispatcher identifier.
	 * @param mixed $dispatcher RequestDispatcher.
	 * @param string $reference Reference dispatcher identifier.
	 */
	public function insert_after($id, $dispatcher, $reference)
	{
		$this->insert($id, $dispatcher, WeightedDispatcher::WEIGHT_AFTER_PREFIX . $reference);
	}
}
