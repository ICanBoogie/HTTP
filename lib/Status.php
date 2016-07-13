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

	const CONTINUE_ = 100;
	const SWITCHING_PROTOCOLS = 101;
	const OK = 200;
	const CREATED = 201;
	const ACCEPTED = 202;
	const NON_AUTHORITATIVE_INFORMATION = 203;
	const NO_CONTENT = 204;
	const RESET_CONTENT = 205;
	const PARTIAL_CONTENT = 206;
	const MULTIPLE_CHOICES = 300;
	const MOVED_PERMANENTLY = 301;
	const FOUND = 302;
	const SEE_OTHER = 303;
	const NOT_MODIFIED = 304;
	const USE_PROXY = 305;
	const TEMPORARY_REDIRECT = 307;
	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const PAYMENT_REQUIRED = 402;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const METHOD_NOT_ALLOWED = 405;
	const NOT_ACCEPTABLE = 406;
	const PROXY_AUTHENTICATION_REQUIRED = 407;
	const REQUEST_TIMEOUT = 408;
	const CONFLICT = 409;
	const GONE = 410;
	const LENGTH_REQUIRED = 411;
	const PRECONDITION_FAILED = 412;
	const REQUEST_ENTITY_TOO_LARGE = 413;
	const REQUEST_URI_TOO_LONG = 414;
	const UNSUPPORTED_MEDIA_TYPE = 415;
	const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED = 417;
	const I_M_A_TEAPOT = 418;
	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const BAD_GATEWAY = 502;
	const SERVICE_UNAVAILABLE = 503;
	const GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;

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

    /**
     * @param int $code
     */
	protected function set_code($code)
	{
		self::assert_code_is_valid($code);

		$this->code = $code;
	}

    /**
     * @return int
     */
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
	 * A status is considered ok when its code is {@link OK}.
	 *
	 * @return bool
	 */
	protected function get_is_ok()
	{
		return $this->code == self::OK;
	}

	/**
	 * Whether the status is forbidden.
	 *
	 * A status is considered forbidden ok when its code is {@link FORBIDDEN}.
	 *
	 * @return bool
	 */
	protected function get_is_forbidden()
	{
		return $this->code == self::FORBIDDEN;
	}

	/**
	 * Whether the status is not found.
	 *
	 * A status is considered not found when its code is {@link NOT_FOUND}.
	 *
	 * @return bool
	 */
	protected function get_is_not_found()
	{
		return $this->code == self::NOT_FOUND;
	}

	/**
	 * Whether the status is empty.
	 *
	 * A status is considered empty when its code is {@link CREATED}, {@link NO_CONTENT} or
	 * {@link NOT_MODIFIED}.
	 *
	 * @return bool
	 */
	protected function get_is_empty()
	{
		static $range = [

			self::CREATED,
			self::NO_CONTENT,
			self::NOT_MODIFIED

		];

		return in_array($this->code, $range);
	}

	/**
	 * Whether the status is cacheable.
	 *
	 * @return bool
	 */
	protected function get_is_cacheable()
	{
		static $range = [

			self::OK,
			self::NON_AUTHORITATIVE_INFORMATION,
			self::MULTIPLE_CHOICES,
			self::MOVED_PERMANENTLY,
			self::FOUND,
			self::NOT_FOUND,
			self::GONE

		];

		return in_array($this->code, $range);
	}

	/**
	 * Message describing the status code.
	 *
	 * @var string
	 */
	private $message;

    /**
     * @param string $message
     */
	protected function set_message($message)
	{
		$this->message = $message;
	}

    /**
     * @return string
     */
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
	public function __construct($code = self::OK, $message = null)
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
