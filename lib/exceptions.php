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
 * The interface is implemented by HTTP exceptions so that they can be easily recognized.
 */
interface Exception
{

}

/**
 * Base class for HTTP exceptions.
 *
 * @deprecated Use ICanBoogie\HTTP\Exception instead
 */
class HTTPError extends \Exception implements Exception
{

}

/**
 * Exception thrown when a resource is not found.
 */
class NotFound extends HTTPError
{
	public function __construct($message='The requested URL was not found on this server.', $code=404, \Exception $previous=null)
	{
		parent::__construct($message, $code, $previous);
	}
}

/**
 * Exception thrown when the server is currently unavailable (because it is overloaded or
 * down for maintenance).
 */
class ServiceUnavailable extends HTTPError
{
	public function __construct($message="The server is currently unavailable (because it is overloaded or down for maintenance).", $code=503, \Exception $previous=null)
	{
		parent::__construct($message, $code, $previous);
	}
}

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

/**
 * Exception thrown to force the redirect of the response.
 *
 * @property-read string $location The location of the redirect.
 */
class ForceRedirect extends HTTPError
{
	use GetterTrait;

	private $location;

	protected function get_location()
	{
		return $this->location;
	}

	public function __construct($location, $code=302, \Exception $previous=null)
	{
		$this->location = $location;

		parent::__construct(\ICanBoogie\format("Location: %location", [ 'location' => $location ]), $code, $previous);
	}
}