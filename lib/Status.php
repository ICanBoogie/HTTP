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
 * Class Status
 *
 * @property int $code
 * @property string $message
 *
 * @property-read bool $is_cacheable Whether the status is cacheable.
 * @property-read bool $is_client_error Whether the status is a client error.
 * @property-read bool $is_empty Whether the status is empty.
 * @property-read bool $is_forbidden Whether the status is forbidden.
 * @property-read bool $is_informational Whether the status is informational.
 * @property-read bool $is_not_found Whether the status is not found.
 * @property-read bool $is_ok Whether the status is ok.
 * @property-read bool $is_redirect Whether the status is a redirection.
 * @property-read bool $is_server_error Whether the status is a server error.
 * @property-read bool $is_successful Whether the status is successful.
 * @property-read bool $is_valid Whether the status is valid.
 */
class Status
{
	use AccessorTrait;

	/**
	 * HTTP status codes and messages.
	 *
	 * @var array
	 */
	static public $codes_and_messages = [

		100 => "Continue",
		101 => "Switching Protocols",

		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",

		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",

		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",
		418 => "I'm a teapot",

		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported"

	];

	/**
	 * Creates a new instance from the provided status.
	 *
	 * @param $status
	 *
	 * @return Status
	 *
	 * @throws \InvalidArgumentException When the HTTP status code is not valid.
	 */
	static public function from($status)
	{
		if ($status instanceof self)
		{
			return $status;
		}

		$message = null;

		if (is_array($status))
		{
			list($code, $message) = $status;
		}
		elseif (is_numeric($status))
		{
			$code = (int) $status;
		}
		else
		{
			if (!preg_match('/^(\d{3})\s+(.+)$/', $status, $matches))
			{
				throw new \InvalidArgumentException("Invalid status: $status.");
			}

			list(, $code, $message) = $matches;
		}

		return new static($code, $message);
	}

	/**
	 * Asserts that a status code is valid.
	 *
	 * @param $code
	 *
	 * @throws StatusCodeNotValid if the status code is not valid.
	 */
	static private function assert_code_is_valid($code)
	{
		if ($code >= 100 && $code < 600)
		{
			return;
		}

		throw new StatusCodeNotValid($code);
	}

	/**
	 * Status code.
	 *
	 * @var int
	 */
	private $code;

	protected function set_code($code)
	{
		self::assert_code_is_valid($code);

		$this->code = $code;
	}

	protected function get_code()
	{
		return $this->code;
	}

	/**
	 * Whether the status is valid.
	 *
	 * A status is considered valid when its code is between 100 and 600, 100 included.
	 *
	 * @return bool
	 */
	protected function get_is_valid()
	{
		return $this->code >= 100 && $this->code < 600;
	}

	/**
	 * Whether the status is informational.
	 *
	 * A status is considered informational when its code is between 100 and 200, 100 included.
	 *
	 * @return bool
	 */
	protected function get_is_informational()
	{
		return $this->code >= 100 && $this->code < 200;
	}

	/**
	 * Whether the status is successful.
	 *
	 * A status is considered successful when its code is between 200 and 300, 200 included.
	 *
	 * @return bool
	 */
	protected function get_is_successful()
	{
		return $this->code >= 200 && $this->code < 300;
	}

	/**
	 * Whether the status is a redirection.
	 *
	 * A status is considered to be a redirection when its code is between 300 and 400, 300
	 * included.
	 *
	 * @return bool
	 */
	protected function get_is_redirect()
	{
		return $this->code >= 300 && $this->code < 400;
	}

	/**
	 * Whether the status is a client error.
	 *
	 * A status is considered a client error when its code is between 400 and 500, 400
	 * included.
	 *
	 * @return bool
	 */
	protected function get_is_client_error()
	{
		return $this->code >= 400 && $this->code < 500;
	}

	/**
	 * Whether the status is a server error.
	 *
	 * A status is considered a server error when its code is between 500 and 600, 500
	 * included.
	 *
	 * @return bool
	 */
	protected function get_is_server_error()
	{
		return $this->code >= 500 && $this->code < 600;
	}

	/**
	 * Whether the status is ok.
	 *
	 * A status is considered ok when its code is 200.
	 *
	 * @return bool
	 */
	protected function get_is_ok()
	{
		return $this->code == 200;
	}

	/**
	 * Whether the status is forbidden.
	 *
	 * A status is considered forbidden ok when its code is 403.
	 *
	 * @return bool
	 */
	protected function get_is_forbidden()
	{
		return $this->code == 403;
	}

	/**
	 * Whether the status is not found.
	 *
	 * A status is considered not found when its code is 404.
	 *
	 * @return bool
	 */
	protected function get_is_not_found()
	{
		return $this->code == 404;
	}

	/**
	 * Whether the status is empty.
	 *
	 * A status is considered empty when its code is 201, 204 or 304.
	 *
	 * @return bool
	 */
	protected function get_is_empty()
	{
		static $range = [ 201, 204, 304 ];

		return in_array($this->code, $range);
	}

	/**
	 * Whether the status is cacheable.
	 *
	 * @return bool
	 */
	protected function get_is_cacheable()
	{
		static $range = [ 200, 203, 300, 301, 302, 404, 410 ];

		return in_array($this->code, $range);
	}

	/**
	 * Message describing the status code.
	 *
	 * @var string
	 */
	private $message;

	protected function set_message($message)
	{
		$this->message = $message;
	}

	protected function get_message()
	{
		$message = $this->message;
		$code = $this->code;

		if (!$message && $code)
		{
			$message = self::$codes_and_messages[$code];
		}

		return $message;
	}

	/**
	 * @param int $code
	 * @param string|null $message
	 */
	public function __construct($code = 200, $message = null)
	{
		self::assert_code_is_valid($code);

		$this->code = $code;
		$this->message = $message ?: self::$codes_and_messages[$code];
	}

	public function __toString()
	{
		return "$this->code " . $this->get_message();
	}
}
