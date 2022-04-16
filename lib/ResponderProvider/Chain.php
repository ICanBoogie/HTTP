<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\ResponderProvider;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\ResponderProvider;

/**
 * Tries a chain until a provider is found for a request.
 */
final class Chain implements ResponderProvider
{
    /**
     * @param iterable<ResponderProvider> $chain
     */
    public function __construct(
        private readonly iterable $chain
    ) {
    }

    public function responder_for_request(Request $request): ?Responder
    {
        foreach ($this->chain as $provider) {
            $responder = $provider->responder_for_request($request);

            if ($responder) {
                return $responder;
            }
        }

        return null;
    }
}
