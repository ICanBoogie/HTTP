<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when the server is currently unavailable (because it is overloaded or
 * down for maintenance).
 */
class ServiceUnavailable extends ServerError implements Exception
{
    public const DEFAULT_MESSAGE = "The server is currently unavailable"
    . " (because it is overloaded or down for maintenance).";

    /**
     * @inheritdoc
     */
    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        int $code = ResponseStatus::STATUS_SERVICE_UNAVAILABLE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
