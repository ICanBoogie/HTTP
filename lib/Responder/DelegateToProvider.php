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
 * A {@link Responder} that delegates to a matching {@link Responder}, via a {@link ResponderProvider}.
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
