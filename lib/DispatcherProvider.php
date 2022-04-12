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
final class DispatcherProvider
{
    /**
     * @var callable|null {@link Dispatcher} provider
     */
    private static $provider;

    /**
     * Return the current provider, or `null` if there is none.
     */
    public static function defined(): ?callable
    {
        return self::$provider;
    }

    /**
     * Define a {@link Dispatcher} provider.
     *
     * @return callable|null The previous provider, or `null` if none was defined.
     */
    public static function define(callable $provider): ?callable
    {
        $previous = self::$provider;

        self::$provider = $provider;

        return $previous;
    }

    /**
     * Undefine the current {@link Dispatcher} provider.
     */
    public static function undefine(): void
    {
        self::$provider = null;
    }

    /**
     * Return a {@link Dispatcher} instance using the current provider.
     *
     * @throws DispatcherProviderNotDefined if no provider is defined.
     */
    public static function provide(): Dispatcher
    {
        $provider = self::$provider;

        if (!$provider) {
            throw new DispatcherProviderNotDefined();
        }

        return $provider();
    }
}
