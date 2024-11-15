<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when user authentication is required.
 *
 * Event hooks may rescue the exception and provide a login form instead.
 */
class AuthenticationRequired extends ClientError implements SecurityError
{
    public const string DEFAULT_MESSAGE = "The requested URL requires authentication.";

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
