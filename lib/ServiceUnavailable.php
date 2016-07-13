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
 * Exception thrown when the server is currently unavailable (because it is overloaded or
 * down for maintenance).
 */
class ServiceUnavailable extends ServerError implements Exception
{
    const DEFAULT_MESSAGE = "The server is currently unavailable (because it is overloaded or down for maintenance).";

	/**
	 * @inheritdoc
	 */
	public function __construct($message = self::DEFAULT_MESSAGE, $code = Status::SERVICE_UNAVAILABLE, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
