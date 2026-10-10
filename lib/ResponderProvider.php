<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

/**
 * A provider that matches a request with a responder.
 */
interface ResponderProvider
{
    /**
     * Find a responder to handle the request.
     */
    public function responder_for_request(Request $request): ?Responder;
}
