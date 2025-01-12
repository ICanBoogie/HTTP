<?php

namespace ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\ResponderProvider;
use ICanBoogie\HTTP\Response;

/**
 * A {@see Responder} that delegates to a matching {@see Responder}, via a {@see ResponderProvider}.
 */
final class DelegateToProvider implements Responder
{
    public function __construct(
        private readonly ResponderProvider $responders
    ) {
    }

    /**
     * @throws NotFound if there's no responder for the request.
     */
    public function respond(Request $request): Response
    {
        $responder = $this->responders->responder_for_request($request);

        if (!$responder) {
            throw new NotFound();
        }

        return $responder->respond($request);
    }
}
