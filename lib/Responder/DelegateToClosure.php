<?php

namespace ICanBoogie\HTTP\Responder;

use Closure;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Response;

/**
 * A {@see Responder} that delegates to a {@see Closure}.
 */
final readonly class DelegateToClosure implements Responder
{
    public function __construct(
        private Closure $closure
    ) {
    }

    public function respond(Request $request): Response
    {
        return ($this->closure)($request);
    }
}
