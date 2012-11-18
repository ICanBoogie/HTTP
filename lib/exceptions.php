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

/**
 * Base class for HTTP exceptions.
 */
class HTTPError extends \Exception
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
 */
class MethodNotSupported extends HTTPError
{
	public function __construct($method, $code=500, \Exception $previous=null)
	{
		parent::__construct(\ICanboogie\format('Method not supported: %method', array('method' => $method)), $code, $previous);
	}
}

/**
 * Exception thrown when the HTTP status code is not valid.
 */
class StatusCodeNotValid extends \InvalidArgumentException
{
	public function __construct($status_code, $code=500, \Exception $previous=null)
	{
		parent::__construct("Status code not valid: {$status_code}.", $code, $previous);
	}
}