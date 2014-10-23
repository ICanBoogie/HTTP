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
 * Events:
 *
 * - `ICanBoogie\HTTP\Dispatcher::dispatch:before`: {@link Dispatcher\BeforeDispatchEvent}.
 * - `ICanBoogie\HTTP\Dispatcher::dispatch`: {@link Dispatcher\DispatchEvent}.
 * - `ICanBoogie\HTTP\Dispatcher::rescue`: {@link ICanBoogie\Exception\RescueEvent}.
 */
class Dispatcher implements \ArrayAccess, \IteratorAggregate, DispatcherInterface
{
	/**
	 * The dispatchers called during the dispatching of the request.
	 *
	 * @var array[string]callable|string
	 */
	protected $dispatchers = [];

	/**
	 * The weights of the dispatchers.
	 *
	 * @var array[string]mixed
	 */
	protected $dispatchers_weight = [];

	protected $dispatchers_order;

	/**
	 * Initializes the {@link $dispatchers} property.
	 *
	 * Dispatchers can be defined as callable or class name. If a dispatcher definition is not a
	 * callable it is used as class name to instantiate a dispatcher.
	 */
	public function __construct(array $dispatchers=[])
	{
		foreach ($dispatchers as $dispatcher_id => $dispatcher)
		{
			$this[$dispatcher_id] = $dispatcher;
		}
	}

	/**
	 * Dispatches the request to retrieve a {@link Response}.
	 *
	 * The request is dispatched by the {@link dispatch()} method. If an exception is thrown
	 * during the dispatch the {@link rescue()} method is used to rescue the exception and
	 * retrieve a {@link Response}.
	 *
	 * ## HEAD requests
	 *
	 * If a {@link NotFound} exception is caught during the dispatching of a request with a
	 * {@link Request::METHOD_HEAD} method the following happens:
	 *
	 * 1. The request is cloned and the method of the cloned request is changed to
	 * {@link Request::METHOD_GET}.
	 * 2. The cloned method is dispatched.
	 * 3. If the result is *not* a {@link Response} instance, the result is returned.
	 * 4. Otherwise, a new {@link Response} instance is created with a `null` body, but the status
	 * code and headers of the original response.
	 * 5. The new response is returned.
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
			if ($request->method === Request::METHOD_HEAD && $e instanceof NotFound)
			{
				$get_request = clone $request;
				$get_request->method = Request::METHOD_GET;

				$response = $this($get_request);

				if (!($response instanceof Response))
				{
					return $response;
				}

				return new Response(null, $response->status, $response->headers);
			}

			return $this->rescue($e, $request);
		}
	}

	/**
	 * Checks if the dispatcher is defined.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 *
	 * @return `true` if the dispatcher is defined, `false` otherwise.
	 */
	public function offsetExists($dispatcher_id)
	{
		return isset($this->dispatchers[$dispatcher_id]);
	}

	/**
	 * Returns a dispatcher.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 */
	public function offsetGet($dispatcher_id)
	{
		if (!$this->offsetExists($dispatcher_id))
		{
			throw new DispatcherNotDefined($dispatcher_id);
		}

		return $this->dispatchers[$dispatcher_id];
	}

	/**
	 * Defines a dispatcher.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 * @param mixed $dispatcher The dispatcher class or callback.
	 */
	public function offsetSet($dispatcher_id, $dispatcher)
	{
		$weight = 0;

		if ($dispatcher instanceof WeightedDispatcher)
		{
			$weight = $dispatcher->weight;
			$dispatcher = $dispatcher->dispatcher;
		}

		$this->dispatchers[$dispatcher_id] = $dispatcher;
		$this->dispatchers_weight[$dispatcher_id] = $weight;
		$this->dispatchers_order = null;
	}

	/**
	 * Removes a dispatcher.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 */
	public function offsetUnset($dispatcher_id)
	{
		unset($this->dispatchers[$dispatcher_id]);
	}

	public function getIterator()
	{
		if (!$this->dispatchers_order)
		{
			$weights = $this->dispatchers_weight;

			$this->dispatchers_order = \ICanBoogie\sort_by_weight($this->dispatchers, function($v, $k) use($weights) {

				return $weights[$k];

			});
		}

		return new \ArrayIterator($this->dispatchers_order);
	}

	/**
	 * Dispatches a request using the defined dispatchers.
	 *
	 * The method iterates over the defined dispatchers until one of them returns a
	 * {@link Response} instance. If an exception is throw during the dispatcher execution and
	 * the dispatcher implements the {@link DispatcherInterface} interface then its
	 * {@link DispatcherInterface::rescue} method is invoked to rescue the exception, otherwise the
	 * exception is just re-thrown.
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
			foreach ($this as $id => $dispatcher) // MOVE some to AGGREGATE
			{
				#
				# If the dispatcher is not a callable then it is considered as a class name, which
				# is used to instantiate a dispatcher.
				#

				if (!($dispatcher instanceof CallableDispatcher))
				{
					$this->dispatchers[$id] = $dispatcher = is_callable($dispatcher) ? new CallableDispatcher($dispatcher) : new $dispatcher;
				}

				try
				{
					$request->context->dispatcher = $dispatcher;

					$response = call_user_func($dispatcher, $request);
				}
				catch (\Exception $e)
				{
					if (!($dispatcher instanceof DispatcherInterface))
					{
						throw $e;
					}

					$response = $dispatcher->rescue($e, $request);
				}

				if ($response) break;

				$request->context->dispatcher = null;
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
	 * If a response is finally obtained, the `X-ICanBoogie-Rescued-Exception` header is added to
	 * indicate where the exception was thrown from.
	 *
	 * @param \Exception $exception The exception to rescue.
	 * @param Request $request The current request.
	 *
	 * @return Response
	 *
	 * @throws \Exception The exception is re-thrown if it could not be rescued.
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		$response = null;

		new \ICanBoogie\Exception\RescueEvent($exception, $request, $response);

		if (!$response)
		{
			if ($exception instanceof ForceRedirect)
			{
				return new RedirectResponse($exception->location, $exception->getCode());
			}

			throw $exception;
		}

		$pathname = $exception->getFile();
		$root = $_SERVER['DOCUMENT_ROOT'];

		if ($root && strpos($pathname, $root) === 0)
		{
			$pathname = substr($pathname, strlen($root));
		}

		$response->headers['X-ICanBoogie-Rescued-Exception'] = $pathname . '@' . $exception->getLine();

		return $response;
	}
}

/**
 * Wrapper for callable dispatchers.
 */
class CallableDispatcher implements DispatcherInterface
{
	private $callable;

	public function __construct($callable)
	{
		$this->callable = $callable;
	}

	public function __invoke(Request $request)
	{
		return call_user_func($this->callable, $request);
	}

	public function rescue(\Exception $exception, Request $request)
	{
		throw $exception;
	}
}