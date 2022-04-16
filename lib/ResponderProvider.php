<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

/**
 * A provider that matches a request with a responder.
 */
interface ResponderProvider
{
    /**
     * Find a responder to handle the request.
     */
    public function responder_for_request(Request $request): ?Responder;
}
