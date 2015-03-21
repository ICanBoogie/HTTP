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

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Event class for the `Exception:rescue` event type.
 *
 * Third parties may use this event to provide a response for the exception.
 *
 * @property \Exception $exception
 * @property Response $response
 */
class RescueEvent extends Event
{
	/**
	 * Reference to the response.
	 *
	 * @var Response
	 */
	private $response;

	protected function get_response()
	{
		return $this->response;
	}

	protected function set_response(Response $response = null)
	{
		$this->response = $response;
	}

	/**
	 * Reference to the exception.
	 *
	 * @var \Exception
	 */
	private $exception;

	protected function get_exception()
	{
		return $this->exception;
	}

	protected function set_exception(\Exception $exception)
	{
		$this->exception = $exception;
	}

	/**
	 * The request.
	 *
	 * @var Request
	 */
	private $request;

	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * The event is constructed with the type `rescue`.
	 *
	 * @param \Exception $target
	 * @param Request $request The request.
	 * @param Response|null $response Reference to the response.
	 */
	public function __construct(\Exception &$target, Request $request, Response &$response = null)
	{
		$this->response = &$response;
		$this->exception = &$target;
		$this->request = $request;

		parent::__construct($target, 'rescue');
	}
}
