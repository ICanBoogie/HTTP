<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Responds to a request.
 */
interface Responder
{
    /**
     * @throws Throwable if the response cannot be produced.
     */
    public function respond(Request $request): Response;
}
