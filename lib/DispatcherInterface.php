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
 * Dispatcher interface.
 */
interface DispatcherInterface
{
	/**
	 * Process the request.
	 *
	 * @param Request $request
	 *
	 * @return Response A response to the tequest.
	 */
	public function __invoke(Request $request);

	/**
	 * Rescues the exception that was thrown during the request process.
	 *
	 * @param \Exception $exception
	 *
	 * @return Response A response to the request exception.
	 *
	 * @throws \Exception when the request exception cannot be rescued.
	 */
	public function rescue(\Exception $exception, Request $request);
}
