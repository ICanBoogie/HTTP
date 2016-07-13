<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Event class for the `ICanBoogie\HTTP\RequestDispatcher::dispatch:before` event.
 *
 * Event hooks may use this event to provide a response to the request before the
 * domain dispatchers are invoked. The event is usually used by event hooks to redirect requests
 * or provide cached responses.
 *
 * @property-read Request $request
 * @property Response $response
 */
class BeforeDispatchEvent extends Event
{
    const TYPE = 'dispatch:before';

	/**
	 * The request.
	 *
	 * @var Request
	 */
	private $request;

    /**
     * @return Request
     */
	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * Reference to the response.
	 *
	 * @var Response|null
	 */
	private $response;

    /**
     * @return Response|null
     */
	protected function get_response()
	{
		return $this->response;
	}

    /**
     * @param Response|null $response
     */
	protected function set_response(Response $response = null)
	{
		$this->response = $response;
	}

	/**
	 * The event is constructed with the type `dispatch:before`.
	 *
	 * @param RequestDispatcher $target
	 * @param Request $request
	 * @param Response|null $response Reference to the response.
	 */
	public function __construct(RequestDispatcher $target, Request $request, Response &$response = null)
	{
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, self::TYPE);
	}
}
