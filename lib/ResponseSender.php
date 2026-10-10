<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

/**
 * Sends a response to the client.
 */
interface ResponseSender
{
    /**
     * Sends a response.
     *
     * The response is obtained with {@see Response::finalize()}, which resolves it for a request,
     * and omits the body of a response to a `HEAD` request.
     */
    public function send(FinalResponse $response): void;
}
