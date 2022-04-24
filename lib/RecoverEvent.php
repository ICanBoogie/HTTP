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
 * Listeners may use this event to provide a response or replace the exception.
 */
class RecoverEvent extends Event
{
    public ?Response $response;
    public Throwable $exception;

    public function __construct(
        Throwable &$sender,
        public readonly Request $request,
        Response &$response = null
    ) {
        $this->response = &$response;
        $this->exception = &$sender;

        parent::__construct($sender);
    }
}
