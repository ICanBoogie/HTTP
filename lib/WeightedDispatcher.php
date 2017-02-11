<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use ICanBoogie\Accessor\AccessorTrait;

/**
 * Used to define a dispatcher and its weight.
 *
 * ```php
 * <?php
 *
 * $dispatcher['my'] = new WeightedDispatcher('callback', 'before:that_other_dispatcher');
 * ```
 *
 * @property-read string|Dispatcher $dispatcher
 * @property-read int|string $weight
 */
class WeightedDispatcher
{
	use AccessorTrait;

	const WEIGHT_TOP = 'top';
	const WEIGHT_BOTTOM = 'bottom';
	const WEIGHT_BEFORE_PREFIX = 'before:';
	const WEIGHT_AFTER_PREFIX = 'after:';
	const WEIGHT_DEFAULT = 0;

	/**
	 * @var Dispatcher|string
	 */
	private $dispatcher;

	/**
	 * @return Dispatcher|string
	 */
	protected function get_dispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * @var int|string
	 */
	private $weight;

	/**
	 * @return int|string
	 */
	protected function get_weight()
	{
		return $this->weight;
	}

	/**
	 * Initializes the {@link $dispatcher} and {@link $weight} properties.
	 *
	 * @param Dispatcher|string $dispatcher
	 * @param int|string $weight
	 */
	public function __construct($dispatcher, $weight = self::WEIGHT_DEFAULT)
	{
		$this->dispatcher = $dispatcher;
		$this->weight = $weight;
	}
}
