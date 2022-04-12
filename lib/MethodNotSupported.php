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
use Throwable;

use function ICanboogie\format;

/**
 * Exception thrown when the HTTP method is not supported.
 *
 * @property-read string $method The unsupported HTTP method.
 */
class MethodNotSupported extends ClientError implements Exception
{
    /**
     * @uses get_method
     */
    use AccessorTrait;

    private string $method;

    protected function get_method(): string
    {
        return $this->method;
    }

    /**
     * @param string $method The unsupported HTTP method.
     */
    public function __construct(
        string $method,
        int $code = Status::INTERNAL_SERVER_ERROR,
        Throwable $previous = null
    ) {
        $this->method = $method;

        parent::__construct(format(
            'Method not supported: %method',
            [ 'method' => $method ]
        ), $code, $previous);
    }
}
