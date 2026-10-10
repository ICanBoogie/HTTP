<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Responder\WithEvent\BeforeRespondEvent;
use ICanBoogie\HTTP\Responder\WithEvent\RespondEvent;
use ICanBoogie\HTTP\Response;

use function ICanBoogie\emit;

/**
 * Decorates a {@see Responder} with {@see BeforeRespondEvent} and {@see RespondEvent}.
 */
final readonly class WithEvent implements Responder
{
    public function __construct(
        private Responder $responder
    ) {
    }

    public function respond(Request $request): Response
    {
        // `$response` is an out parameter: a listener of `BeforeRespondEvent` may provide a response
        // through the event, in which case the wrapped responder is not invoked.
        emit(new BeforeRespondEvent($request, $response));

        $response ??= $this->responder->respond($request);

        emit(new RespondEvent($request, $response));

        return $response;
    }
}
