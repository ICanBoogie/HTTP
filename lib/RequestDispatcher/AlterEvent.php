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
 * @property RequestDispatcher $instance
 */
final class AlterEvent extends Event
{
    const TYPE = 'alter';

	/**
	 * Reference to the target instance.
	 *
	 * @var RequestDispatcher
	 */
	private $instance;

	protected function get_instance(): RequestDispatcher
	{
		return $this->instance;
	}

	protected function set_instance(RequestDispatcher $dispatcher): void
	{
		$this->instance = $dispatcher;
	}

	/**
	 * The event is constructed with the type {@link self::TYPE}.
	 *
	 * @param RequestDispatcher $target
	 */
	public function __construct(RequestDispatcher &$target)
	{
		$this->instance = &$target;

		parent::__construct($target, self::TYPE);
	}

	/**
	 * Inserts a dispatcher.
	 *
	 * @param string $id RequestDispatcher identifier.
	 * @param mixed $dispatcher RequestDispatcher.
	 * @param int|string $weight
	 */
	public function insert(string $id, $dispatcher, $weight = 0)
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
	public function insert_before(string $id, $dispatcher, string $reference)
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
	public function insert_after(string $id, $dispatcher, string $reference)
	{
		$this->insert($id, $dispatcher, WeightedDispatcher::WEIGHT_AFTER_PREFIX . $reference);
	}
}
