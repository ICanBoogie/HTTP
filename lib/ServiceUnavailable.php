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
class ServiceUnavailable extends \Exception implements Exception
{
	public function __construct($message = "The server is currently unavailable (because it is overloaded or down for maintenance).", $code = 503, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
