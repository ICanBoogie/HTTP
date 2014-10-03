<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Exception;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Event class for the `Exception:rescue` event type.
 *
 * Third parties may use this event to provide a response for the exception.
 */
class RescueEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the response.
	 *
	 * @var Response
	 */
	public $response;

	/**
	 * Reference to the exception.
	 *
	 * @var \Exception
	 */
	public $exception;

	/**
	 * The request.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `rescue`.
	 *
	 * @param \Exception $target
	 * @param Request $request The request.
	 * @param Response|null Reference to the response.
	 */
	public function __construct(\Exception &$target, Request $request, &$response)
	{
		if ($response !== null && !($response instanceof Response))
		{
			throw new \InvalidArgumentException('$response must be an instance of ICanBoogie\HTTP\Response. Given: ' . (is_object($response) ? get_class($response) : gettype($response)) . '.');
		}

		$this->response = &$response;
		$this->exception = &$target;
		$this->request = $request;

		parent::__construct($target, 'rescue');
	}
}