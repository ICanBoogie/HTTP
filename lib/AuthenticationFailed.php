<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when user authentication failed.
 */
class AuthenticationFailed extends ClientError implements SecurityError
{
    public const string DEFAULT_MESSAGE = "Unable to authenticate";

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
