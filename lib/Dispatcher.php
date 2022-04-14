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
 * Dispatcher interface.
 */
interface Dispatcher
{
    /**
     * Process the request.
     *
     * @return Response|null A response or `null` if the dispatcher cannot handle the request.
     */
    public function __invoke(Request $request): ?Response;

    /**
     * Rescues the exception that was thrown during the request process.
     *
     * @return Response A response to the request exception.
     *
     * @throws Throwable when the request exception cannot be rescued.
     */
    public function rescue(Throwable $exception, Request $request): Response;
}
