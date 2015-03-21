<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::dispatch:before` event.
 *
 * Third parties may use this event to provide a response to the request before the dispatchers
 * are invoked. The event is usually used by third parties to redirect requests or provide cached
 * responses.
 *
 * @property-read Request $request
 * @property Response $response
 */
class BeforeDispatchEvent extends Event
{
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
	 * The event is constructed with the type `dispatch:before`.
	 *
	 * @param Dispatcher $target
	 * @param Request $request
	 * @param Response|null $response Reference to the response.
	 */
	public function __construct(Dispatcher $target, Request $request, Response &$response = null)
	{
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, 'dispatch:before');
	}
}
