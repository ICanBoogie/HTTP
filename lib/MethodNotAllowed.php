<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when an HTTP method is not allowed.
 */
class MethodNotAllowed extends ClientError implements Exception
{
    public function __construct(
        public readonly string $method,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Method not allowed: $method.",
            ResponseStatus::STATUS_METHOD_NOT_ALLOWED,
            $previous
        );
    }
}
