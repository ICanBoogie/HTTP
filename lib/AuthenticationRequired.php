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
    public const DEFAULT_MESSAGE = "The requested URL requires authentication.";

	/**
	 * @inheritdoc
	 */
	public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = Status::UNAUTHORIZED, \Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
