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
	 * Whether a provider if defined.
	 *
	 * @return callable|null
	 */
	static public function defined()
	{
		return self::$provider;
	}

	/**
	 * Defines the {@link Dispatcher} provider.
	 *
	 * @param callable $provider
	 *
	 * @return callable|null The previous provider, or `null` if none was defined before.
	 */
	static public function define(callable $provider)
	{
		$previous = self::$provider;

		self::$provider = $provider;

		return $previous;
	}

	/**
	 * Undefine the {@link Dispatcher} provider.
	 */
	static public function undefine()
	{
		self::$provider = null;
	}

	/**
	 * Returns a {@link Dispatcher} instance using the provider.
	 *
	 * @return Dispatcher
	 *
	 * @throws DispatcherProviderNotDefined if no provider is defined.
	 */
	static public function provide()
	{
		$provider = self::$provider;

		if (!$provider)
		{
			throw new DispatcherProviderNotDefined;
		}

		return $provider();
	}
}
