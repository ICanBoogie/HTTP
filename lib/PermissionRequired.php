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
 * Exception thrown when a user lacks a required permission.
 */
class PermissionRequired extends ClientError implements SecurityError
{
	/**
	 * @inheritdoc
	 */
	public function __construct($message = "You don't have the required permission.", $code = Status::UNAUTHORIZED, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
