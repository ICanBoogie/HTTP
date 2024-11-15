<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Responds to a request.
 */
interface Responder
{
    /**
     * @throws Throwable if the response can't be produced.
     */
    public function respond(Request $request): Response;
}
