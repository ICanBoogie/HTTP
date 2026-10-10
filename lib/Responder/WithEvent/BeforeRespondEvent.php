<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Responder\WithEvent;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Listeners can modify the request or provide a response.
 */
class BeforeRespondEvent extends Event
{
    public function __construct(
        public readonly Request $request,
        public ?Response &$response = null
    ) {
        parent::__construct();
    }
}
