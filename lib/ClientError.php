<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a client error occurs.
 */
class ClientError extends \Exception implements Exception
{
    public function __construct(
        ?string $message = null,
        int $code = ResponseStatus::STATUS_BAD_REQUEST,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
