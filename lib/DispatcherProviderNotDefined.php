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
 * Exception thrown in attempt to obtain a dispatcher when no provider is defined.
 */
class DispatcherProviderNotDefined extends \LogicException implements Exception
{
	public const DEFAULT_MESSAGE = "No provider is defined yet. Please define one with `DispatcherProvider::define(\$provider)`.";

    /**
     * @inheritdoc
     */
	public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = Status::INTERNAL_SERVER_ERROR, \Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
