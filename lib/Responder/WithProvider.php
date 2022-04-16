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

use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\ResponderProvider;
use ICanBoogie\HTTP\Response;

/**
 * A responder that delegates the response to a matching responder.
 */
final class WithProvider implements Responder
{
    public function __construct(
        private readonly ResponderProvider $responders
    ) {
    }

    public function respond(Request $request): Response
    {
        $responder = $this->responders->responder_for_request($request);

        if (!$responder) {
            throw new NotFound();
        }

        return $responder->respond($request);
    }
}
