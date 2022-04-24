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

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Responder\WithEvent\BeforeRespondEvent;
use ICanBoogie\HTTP\Responder\WithEvent\RespondEvent;
use ICanBoogie\HTTP\Response;

use function ICanBoogie\emit;

/**
 * Decorates a {@link Responder} with {@link BeforeRespondEvent} and {@link RespondEvent}.
 */
final class WithEvent implements Responder
{
    public function __construct(
        private readonly Responder $responder
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
