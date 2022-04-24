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

use Closure;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Response;

/**
 * A {@link Responder} that delegates to a {@link Closure}.
 */
final class DelegateToClosure implements Responder
{
    public function __construct(
        private readonly Closure $closure
    ) {
    }

    public function respond(Request $request): Response
    {
        return ($this->closure)($request);
    }
}
