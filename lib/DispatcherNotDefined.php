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

use ICanBoogie\Accessor\AccessorTrait;
use LogicException;
use Throwable;

use function ICanBoogie\format;

/**
 * Exception thrown in attempt to obtain a dispatcher that is not defined.
 *
 * @property-read string $dispatcher_id The identifier of the dispatcher.
 */
class DispatcherNotDefined extends LogicException implements Exception
{
    /**
     * @uses get_dispatcher_id
     */
    use AccessorTrait;

    private string $dispatcher_id;

    protected function get_dispatcher_id(): string
    {
        return $this->dispatcher_id;
    }

    public function __construct(
        string $dispatcher_id,
        string $message = null,
        int $code = ResponseStatus::STATUS_INTERNAL_SERVER_ERROR,
        Throwable $previous = null
    ) {
        $this->dispatcher_id = $dispatcher_id;

        parent::__construct($message ?: $this->format_message($dispatcher_id), $code, $previous);
    }

    private function format_message(string $dispatcher_id): string
    {
        return format("The dispatcher %dispatcher_id is not defined.", [

            'dispatcher_id' => $dispatcher_id,

        ]);
    }
}
