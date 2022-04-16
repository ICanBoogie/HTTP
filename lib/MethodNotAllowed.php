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

use Throwable;

/**
 * Exception thrown when an HTTP method is not allowed.
 */
class MethodNotAllowed extends ClientError implements Exception
{
    public function __construct(
        public readonly string $method,
        Throwable $previous = null
    ) {
        parent::__construct(
            "Method not allowed: $method.",
            ResponseStatus::STATUS_METHOD_NOT_ALLOWED,
            $previous
        );
    }
}
