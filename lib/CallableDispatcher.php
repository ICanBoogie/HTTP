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
 * Wrapper for callable dispatchers.
 */
class CallableDispatcher implements Dispatcher
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Request $request): ?Response
    {
        return ($this->callable)($request);
    }

    /**
     * @inheritdoc
     */
    public function rescue(Throwable $exception, Request $request): Response
    {
        throw $exception;
    }
}
