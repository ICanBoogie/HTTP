<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Responder\WithEvent;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Listeners can alter the response.
 */
class RespondEvent extends Event
{
    public ?Response $response;

    public function __construct(
        public readonly Request $request,
        Response &$response = null
    ) {
        $this->response = &$response;

        parent::__construct();
    }
}
