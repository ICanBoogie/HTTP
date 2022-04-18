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

use InvalidArgumentException;

/**
 * Maps options to the environment.
 */
final class RequestOptionsMapper
{
    /**
     * Maps options to environment.
     *
     * The options mapped to the environment are removed from the `$options` array.
     *
     * @param array<RequestOptions::*, mixed> $options Reference to the options.
     * @param array<string, mixed> $env Reference to the environment.
     *
     * @throws InvalidArgumentException on invalid option.
     */
    public static function map(array &$options, array &$env): void
    {
        foreach ($options as $option => &$value) {
            $mapper = self::get_value_mapper($option);

            if ($mapper) {
                $value = $mapper($value);

                if ($value === null) {
                    unset($options[$option]);
                }

                continue;
            }

            $mapper = self::get_env_mapper($option);

            if ($mapper) {
                $mapper($value, $env);

                unset($options[$option]);

                continue;
            }

            throw new InvalidArgumentException("Option not supported: `$option`.");
        }
    }

    /**
     * @phpstan-return (callable(mixed $value): mixed)|null
     */
    private static function get_value_mapper(string $option): ?callable
    {
        return [

            RequestOptions::OPTION_PATH_PARAMS => fn($value) => $value,
            RequestOptions::OPTION_QUERY_PARAMS => fn($value) => $value,
            RequestOptions::OPTION_REQUEST_PARAMS => fn($value) => $value,
            RequestOptions::OPTION_COOKIE => fn($value) => $value,
            RequestOptions::OPTION_FILES => fn($value) => $value,
            RequestOptions::OPTION_HEADERS => fn($value) => ($value instanceof Headers) ? $value : new Headers($value),

        ][$option] ?? null;
    }

    /**
     * Returns request properties mappers.
     *
     * @phpstan-return (callable(mixed $value, array &$env): mixed)|null
     */
    private static function get_env_mapper(string $option): ?callable
    {
        return [

            RequestOptions::OPTION_CACHE_CONTROL => function ($value, array &$env) {
                $env['HTTP_CACHE_CONTROL'] = $value;
            },
            RequestOptions::OPTION_CONTENT_LENGTH => function ($value, array &$env) {
                $env['CONTENT_LENGTH'] = $value;
            },
            RequestOptions::OPTION_IP => function ($value, array &$env) {
                if ($value) {
                    $env['REMOTE_ADDR'] = $value;
                }
            },
            RequestOptions::OPTION_IS_LOCAL => function ($value, array &$env) {
                if ($value) {
                    $env['REMOTE_ADDR'] = '::1';
                }
            },
            RequestOptions::OPTION_IS_XHR => function ($value, array &$env) {
                if ($value) {
                    $env['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
                } else {
                    unset($env['HTTP_X_REQUESTED_WITH']);
                }
            },
            RequestOptions::OPTION_METHOD => function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = $value;
                }
            },
            RequestOptions::OPTION_PATH => function ($value, array &$env) {
                $env['REQUEST_URI'] = $value;
            }, // TODO-20130521: handle query string
            RequestOptions::OPTION_REFERER => function ($value, array &$env) {
                $env['HTTP_REFERER'] = $value;
            },
            RequestOptions::OPTION_URI => function ($value, array &$env) {
                $env['REQUEST_URI'] = $value;
                $qs = strpos($value, '?');
                $env['QUERY_STRING'] = $qs === false ? '' : substr($value, $qs + 1);
            },
            RequestOptions::OPTION_USER_AGENT => function ($value, array &$env) {
                $env['HTTP_USER_AGENT'] = $value;
            },

        ][$option] ?? null;
    }
}
