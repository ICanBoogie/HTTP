<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a user lacks a required permission.
 */
class PermissionRequired extends ClientError implements SecurityError
{
    public const string DEFAULT_MESSAGE = "You don't have the required permission.";

    /**
     * @inheritdoc
     */
    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        int $code = ResponseStatus::STATUS_UNAUTHORIZED,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
