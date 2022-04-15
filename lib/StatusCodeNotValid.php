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

use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;
use Throwable;

use function ICanBoogie\format;

/**
 * Exception thrown when the HTTP status code is not valid.
 *
 * @property-read int $status_code The status code that is not supported.
 */
class StatusCodeNotValid extends InvalidArgumentException implements Exception
{
    /**
     * @uses get_status_code
     */
    use AccessorTrait;

    protected function get_status_code(): int
    {
        return $this->status_code;
    }

    public function __construct(
        private int $status_code,
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
