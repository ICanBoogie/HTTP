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

use ICanBoogie\GetterTrait;

/**
 * Exception thrown when the HTTP method is not supported.
 *
 * @property-read string $method The method that is not supported.
 */
class MethodNotSupported extends HTTPError
{
	use GetterTrait;

	private $method;

	protected function get_method()
	{
		return $this->method;
	}

	public function __construct($method, $code=500, \Exception $previous=null)
	{
		$this->method = $method;

		parent::__construct(\ICanboogie\format('Method not supported: %method', [ 'method' => $method ]), $code, $previous);
	}
}