<?php

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
        emit(new BeforeRespondEvent($request, $response));

        $response ??= $this->responder->respond($request);

        emit(new RespondEvent($request, $response));

        return $response;
    }
}
