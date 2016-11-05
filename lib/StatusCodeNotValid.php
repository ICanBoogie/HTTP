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

use function ICanBoogie\format;

/**
 * Exception thrown when the HTTP status code is not valid.
 *
 * @property-read int $status_code The status code that is not supported.
 */
class StatusCodeNotValid extends \InvalidArgumentException implements Exception
{
	use AccessorTrait;

    /**
     * @var int
     */
	private $status_code;

    /**
     * @return int
     */
	protected function get_status_code()
	{
		return $this->status_code;
	}

	/**
	 * @param int $status_code
	 * @param string|null $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($status_code, $message = null, $code = Status::INTERNAL_SERVER_ERROR, \Exception $previous = null)
	{
		$this->status_code = $status_code;

		parent::__construct($message ?: $this->format_message($status_code), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param int $status_code
	 *
	 * @return string
	 */
	protected function format_message($status_code)
	{
		return format("Status code not valid: %status_code.", [ 'status_code' => $status_code ]);
	}
}
