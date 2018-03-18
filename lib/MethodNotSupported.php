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

/**
 * Exception thrown when the HTTP method is not supported.
 *
 * @property-read string $method The unsupported HTTP method.
 */
class MethodNotSupported extends ClientError implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $method;

	protected function get_method(): string
	{
		return $this->method;
	}

	/**
	 * @param string $method The unsupported HTTP method.
	 * @param int $code
	 * @param \Throwable $previous
	 */
	public function __construct(string $method, int $code = Status::INTERNAL_SERVER_ERROR, \Throwable $previous = null)
	{
		$this->method = $method;

		parent::__construct(\ICanboogie\format('Method not supported: %method', [ 'method' => $method ]), $code, $previous);
	}
}
