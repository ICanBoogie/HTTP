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
 * Exception thrown when a server error occurs.
 *
 * @codeCoverageIgnore
 */
class ServerError extends \Exception implements Exception
{
	/**
	 * @inheritdoc
	 */
	public function __construct($message = null, $code = Status::INTERNAL_SERVER_ERROR, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
