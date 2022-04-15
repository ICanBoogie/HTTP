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
 * Exception thrown when the HTTP method is not supported.
 */
class MethodNotSupported extends ClientError implements Exception
{
    /**
     * @param string $method The unsupported HTTP method.
     */
    public function __construct(
        public readonly string $method,
        Throwable $previous = null
    ) {
        parent::__construct(
            "Unsupported HTTP method: $method.",
            ResponseStatus::STATUS_INTERNAL_SERVER_ERROR,
            $previous
        );
    }
}
