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
 * Exception thrown when user authentication is required.
 *
 * Event hooks may rescue the exception and provide a login form instead.
 */
class AuthenticationRequired extends ClientError implements SecurityError
{
	/**
	 * @inheritdoc
	 */
	public function __construct($message = "The requested URL requires authentication.", $code = Status::UNAUTHORIZED, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
