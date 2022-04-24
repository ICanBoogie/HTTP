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
use Throwable;

use function ICanBoogie\format;

/**
 * Exception thrown when the HTTP status code is not valid.
 */
class StatusCodeNotValid extends InvalidArgumentException implements Exception
{
    public function __construct(
        public readonly int $status_code,
        string $message = null,
        int $code = ResponseStatus::STATUS_INTERNAL_SERVER_ERROR,
        Throwable $previous = null
    ) {
        parent::__construct($message ?: $this->format_message($status_code), $code, $previous);
    }

    private function format_message(int $status_code): string
    {
        return format("Status code not valid: %status_code.", [ 'status_code' => $status_code ]);
    }
}
