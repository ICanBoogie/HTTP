<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\Event;
use Throwable;

/**
 * Listeners may use this event to provide a response or replace the exception.
 */
class RecoverEvent extends Event
{
    public function __construct(
        public Throwable &$exception,
        public readonly Request $request,
        public ?Response &$response = null
    ) {
        parent::__construct($exception);
    }
}
