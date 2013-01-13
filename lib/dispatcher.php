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
 * Dispatches requests.
 *
 * Requests are dispatched using dispatchers which can be altered during the
 * {@link Dispatcher\CollectEvent} event.
 *
 * Events:
 *
 * - `ICanBoogie\HTTP\Dispatcher::collect`: {@link Dispatcher\CollectEvent}.
 * - `ICanBoogie\HTTP\Dispatcher::dispatch:before`: {@link Dispatcher\BeforeDispatchEvent}.
 * - `ICanBoogie\HTTP\Dispatcher::dispatch`: {@link Dispatcher\DispatchEvent}.
 * - `ICanBoogie\HTTP\Dispatcher::rescue`: {@link ICanBoogie\Exception\RescueEvent}.
 */
class Dispatcher implements IDispatcher
{
	/**
	 * The dispatchers called during the dispatching of the request.
	 *
	 * @var array[string]callable|string
	 */
	protected $dispatchers = array();

	/**
	 * Initialiazes the {@link $dispatchers} property.
	 *
	 * Fires {@link Dispatcher\CollectEvent}.
	 *
	 * Dispatchers can be defined as callable or class name. If a dispatcher definition is not a
	 * callable it is used as class name to instantiate a dispatcher.
	 */
	public function __construct(array $dispatchers=array())
	{
		new Dispatcher\CollectEvent($this, $dispatchers);

		$this->dispatchers = $dispatchers;
	}

	/**
	 * Dispatches the request to retrieve a {@link Response}.
	 *
	 * The request is dispatched by the {@link dispatch()} method. If an exception is thrown
	 * during the dispatch the {@link rescue()} method is used to rescue the exception and
	 * retrieve a {@link Response}.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function __invoke(Request $request)
	{
		try
		{
			return $this->dispatch($request);
		}
		catch (\Exception $e)
		{
			return $this->rescue($e, $request);
		}
	}

	/**
	 * Dispatches a request using the defined dispatchers.
	 *
	 * The method iterates over the defined dispatchers until one of them returns a
	 * {@link Response} instance. If an exception is throw during the dispatcher execution and
	 * the dispatcher implements the {@link IDispatcher} interface then its
	 * {@link IDispatcher::rescue} method is invoked to rescue the exception, otherwise the
	 * exception is just rethrown.
	 *
	 * {@link Dispatcher\BeforeDispatchEvent} is fired before dispatchers are traversed. If a
	 * response is provided the dispatchers are skipped.
	 *
	 * {@link Dispatcher\DispatchEvent} is fired before the response is returned. The event is
	 * fired event if the dispatchers didn't return a response. It's the last chance to get one.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 *
	 * @throws NotFound when neither the events nor the dispatchers were able to provide
	 * a {@link Response}.
	 */
	protected function dispatch(Request $request)
	{
		$response = null;

		new Dispatcher\BeforeDispatchEvent($this, $request, $response);

		if (!$response)
		{
			foreach ($this->dispatchers as &$dispatcher)
			{
				#
				# If the dispatcher is not a callable then it is considered as a class name, which
				# is used to instantiate a dispatcher.
				#

				if (!is_callable($dispatcher))
				{
					$dispatcher = new $dispatcher;
				}

				try
				{
					$response = call_user_func($dispatcher, $request);
				}
				catch (\Exception $e)
				{
					if (!($dispatcher instanceof IDispatcher))
					{
						throw $e;
					}

					$response = $dispatcher->rescue($e);
				}

				if ($response) break;
			}
		}

		new Dispatcher\DispatchEvent($this, $request, $response);

		if (!$response)
		{
			throw new NotFound;
		}

		return $response;
	}

	/**
	 * Tries to get a {@link Response} object from an exception.
	 *
	 * {@link \ICanBoogie\Exception\RescueEvent} is fired with the exception as target.
	 * The response provided by one of the event hooks is returned. If there is no response the
	 * exception is thrown again.
	 *
	 * If a response is finaly obtained, the `X-ICanBoogie-Rescued-Exception` header is added to
	 * indicate where the exception was thrown from.
	 *
	 * @param \Exception $exception The exception to rescue.
	 * @param Request $request The current request.
	 *
	 * @return Response
	 *
	 * @throws \Exception The exception is rethrown if it could not be rescued.
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		$response = null;

		new \ICanBoogie\Exception\RescueEvent($exception, $request, $response);

		if (!$response)
		{
			throw $exception;
		}

		$pathname = $exception->getFile();
		$root = $_SERVER['DOCUMENT_ROOT'];

		if (strpos($pathname, $root) === 0)
		{
			$pathname = substr($pathname, strlen($root));
		}

		$response->headers['X-ICanBoogie-Rescued-Exception'] = $pathname . '@' . $exception->getLine();

		return $response;
	}
}

/**
 * Dispatcher interface.
 */
interface IDispatcher
{
	/**
	 * Process the request.
	 *
	 * @param Request $request
	 *
	 * @return Response A response to the tequest.
	 */
	public function __invoke(Request $request);

	/**
	 * Rescues the exception that was thrown during the request process.
	 *
	 * @param \Exception $exception
	 *
	 * @return Response A response to the request exception.
	 *
	 * @throws \Exception when the request exception cannot be rescued.
	 */
	public function rescue(\Exception $exception, Request $request);
}

/*
 * Events
 */

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Response;
use ICanBoogie\HTTP\Request;

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::collect` event.
 *
 * Third parties may use this event to register additionnal dispatchers.
 */
class CollectEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the dispatchers array.
	 *
	 * @var array[string]callable
	 */
	public $dispatchers;

	/**
	 * The event is constructed with the type `collect`.
	 *
	 * @param Dispatcher $target
	 * @param array $payload
	 */
	public function __construct(Dispatcher $target, array &$dispatchers)
	{
		$this->dispatchers = &$dispatchers;

		parent::__construct($target, 'collect');
	}
}

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::dispatch:before` event.
 *
 * Third parties may use this event to provide a response to the request before the dispatchers
 * are invoked. The event is usually used by third parties to redirect requests or provide cached
 * responses.
 */
class BeforeDispatchEvent extends \ICanBoogie\Event
{
	/**
	 * The HTTP request.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * Reference to the HTTP response.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The event is constructed with the type `dispatch:before`.
	 *
	 * @param Dispatcher $target
	 * @param array $payload
	 */
	public function __construct(Dispatcher $target, Request $request, &$response)
	{
		if ($response !== null && !($response instanceof Response))
		{
			throw new \InvalidArgumentException('$response must be an instance of ICanBoogie\HTTP\Response. Given: ' . get_class($response) . '.');
		}

		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, 'dispatch:before');
	}
}

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::dispatch` event.
 *
 * Third parties may use this event to alter the response before it is returned by the dispatcher.
 */
class DispatchEvent extends \ICanBoogie\Event
{
	/**
	 * The request.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * Reference to the response.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The event is constructed with the type `dispatch`.
	 *
	 * @param Dispatcher $target
	 * @param array $payload
	 */
	public function __construct(Dispatcher $target, Request $request, Response &$response)
	{
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, 'dispatch');
	}
}

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
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * Reference tot the exception.
	 *
	 * @var \Exception
	 */
	public $exception;

	/**
	 * The request.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `rescue`.
	 *
	 * @param \Exception $target
	 * @param array $payload
	 */
	public function __construct(\Exception &$target, Request $request, &$response)
	{
		if ($response !== null && !($response instanceof Response))
		{
			throw new \InvalidArgumentException('$response must be an instance of ICanBoogie\HTTP\Response. Given: ' . get_class($response) . '.');
		}

		$this->response = &$response;
		$this->exception = &$target;
		$this->request = $request;

		parent::__construct($target, 'rescue');
	}
}