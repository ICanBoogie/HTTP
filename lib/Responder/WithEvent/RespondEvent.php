<?php

namespace ICanBoogie\HTTP\Responder\WithEvent;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Listeners can alter the response.
 */
class RespondEvent extends Event
{
    public function __construct(
        public readonly Request $request,
        public Response &$response,
    ) {
        parent::__construct();
    }
}
