<?php

namespace ICanBoogie\HTTP;

use InvalidArgumentException;
use Throwable;

/**
 * Exception thrown when the HTTP status code is not valid.
 */
class StatusCodeNotValid extends InvalidArgumentException implements Exception
{
    public function __construct(
        public readonly int $status_code,
        ?string $message = null,
        int $code = ResponseStatus::STATUS_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?: $this->format_message($status_code), $code, $previous);
    }

    private function format_message(int $status_code): string
    {
        return "Status code not valid: $status_code";
    }
}
