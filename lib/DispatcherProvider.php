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
 * Provides a {@link Dispatcher} instance.
 */
class DispatcherProvider
{
	/**
	 * @var callable {@link Dispatcher} provider
	 */
	static private $provider;

	/**
	 * Return the current provider, or `null` if there is none.
	 *
	 * @return callable|null
	 */
	static public function defined(): ?callable
	{
		return self::$provider;
	}

	/**
	 * Define a {@link Dispatcher} provider.
	 *
	 * @param callable $provider
	 *
	 * @return callable|null The previous provider, or `null` if none was defined.
	 */
	static public function define(callable $provider): ?callable
	{
		$previous = self::$provider;

		self::$provider = $provider;

		return $previous;
	}

	/**
	 * Undefine the current {@link Dispatcher} provider.
	 */
	static public function undefine(): void
	{
		self::$provider = null;
	}

	/**
	 * Return a {@link Dispatcher} instance using the current provider.
	 *
	 * @return Dispatcher
	 *
	 * @throws DispatcherProviderNotDefined if no provider is defined.
	 */
	static public function provide(): Dispatcher
	{
		$provider = self::$provider;

		if (!$provider)
		{
			throw new DispatcherProviderNotDefined;
		}

		return $provider();
	}
}
