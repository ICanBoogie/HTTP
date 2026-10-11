<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a server error occurs.
 */
class ServerError extends \Exception implements Exception
{
    public function __construct(
        ?string $message = null,
        int $code = Status::INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
