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

use ICanBoogie\Event;
use Throwable;

/**
 * Event class for the `Exception:recover` event type.
 *
 * Third parties may use this event to provide a response or replace the exception.
 */
class RecoverEvent extends Event
{
    public const TYPE = 'recover';

    public ?Response $response;
    public Throwable $exception;
    public readonly Request $request;

    public function __construct(Throwable &$target, Request $request, Response &$response = null)
    {
        $this->response = &$response;
        $this->exception = &$target;
        $this->request = $request;

        parent::__construct($target, self::TYPE);
    }
}
