<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a client error occurs.
 *
 * @codeCoverageIgnore
 */
class ClientError extends \Exception implements Exception
{
    /**
     * @inheritdoc
     */
    public function __construct(
        ?string $message = null,
        int $code = ResponseStatus::STATUS_BAD_REQUEST,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
