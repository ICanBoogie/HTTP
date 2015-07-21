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
 * Wrapper for callable dispatchers.
 */
class CallableDispatcher implements Dispatcher
{
	private $callable;

	/**
	 * @param callable $callable
	 */
	public function __construct($callable)
	{
		$this->callable = $callable;
	}

	/**
	 * @inheritdoc
	 */
	public function __invoke(Request $request)
	{
		$callable = $this->callable;

		return $callable instanceof \Closure ? $callable($request) : call_user_func($callable, $request);
	}

	/**
	 * @inheritdoc
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		throw $exception;
	}
}
