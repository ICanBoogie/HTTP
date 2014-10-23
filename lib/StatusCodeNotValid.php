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
 * Exception thrown when the HTTP status code is not valid.
 *
 * @property-read int $status_code The status code that is not supported.
 */
class StatusCodeNotValid extends \InvalidArgumentException implements Exception
{
	use GetterTrait;

	private $status_code;

	protected function get_status_code()
	{
		return $this->status_code;
	}

	public function __construct($status_code, $code=500, \Exception $previous=null)
	{
		$this->status_code = $status_code;

		parent::__construct(\ICanBoogie\format("Status code not valid: %status_code.", [ 'status_code' => $status_code ]), $code, $previous);
	}
}
