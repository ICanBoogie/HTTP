<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\RecoverEvent;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Response;
use Throwable;

use function ICanBoogie\emit;

/**
 * Decorates a {@see Responder} with exception recovery mechanism.
 *
 * When a {@see Throwable} is caught, a {@see RecoverEvent} is emitted. Listeners can provide a response or replace
 * the exception.
 */
final readonly class WithRecovery implements Responder
{
    public function __construct(
        private Responder $responder
    ) {
    }

    public function respond(Request $request): Response
    {
        try {
            return $this->responder->respond($request);
        } catch (Throwable $e) {
            return $this->rescue($e, $request);
        }
    }

    /**
     * @throws Throwable
     */
    private function rescue(Throwable $exception, Request $request): Response
    {
        // `$response` is an out parameter: `emit()` passes the event arguments by reference, and a
        // listener may assign a response to the event, which defines `$response` here.
        emit(new RecoverEvent($exception, $request, $response));

        return $response ?? throw $exception;
    }
}
