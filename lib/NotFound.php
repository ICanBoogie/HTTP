<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown when a resource is not found.
 */
class NotFound extends ClientError implements Exception
{
    public const DEFAULT_MESSAGE = "The requested URL was not found on this server.";

    /**
     * @inheritdoc
     */
    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        int $code = ResponseStatus::STATUS_NOT_FOUND,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
