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

/**
 * Used to defined a dispatcher and its weight.
 *
 * <pre>
 * <?php
 *
 * $dispatcher['my'] = new WeightedDispatcher('callback', 'before:that_other_dispatcher');
 * </pre>
 */
class WeightedDispatcher
{
	public $dispatcher;

	public $weight;

	public function __construct($dispatcher, $weight)
	{
		$this->dispatcher = $dispatcher;
		$this->weight = $weight;
	}
}
