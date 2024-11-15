<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a resource is not found.
 */
class NotFound extends ClientError implements Exception
{
    public const string DEFAULT_MESSAGE = "The requested URL was not found on this server.";

    /**
     * @inheritdoc
     */
    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        int $code = ResponseStatus::STATUS_NOT_FOUND,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
