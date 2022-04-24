<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Responder;

use ICanBoogie\HTTP\RecoverEvent;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Response;
use Throwable;

use function ICanBoogie\emit;

/**
 * Decorates a {@link Responder} with exception recovery mechanism.
 *
 * When a {@link Throwable} is caught, a {@link RecoverEvent} is emitted. Listeners can provide a response or replace
 * the exception.
 */
final class WithRecovery implements Responder
{
    public function __construct(
        private readonly Responder $responder
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
        emit(new RecoverEvent($exception, $request, $response));

        return $response ?? throw $exception;
    }
}
