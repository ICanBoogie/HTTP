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

use Throwable;

/**
 * Responds to a request.
 */
interface Responder
{
    /**
     * @throws Throwable if the response cannot be produced.
     */
    public function respond(Request $request): Response;
}
