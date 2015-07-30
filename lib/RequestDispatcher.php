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

use ICanBoogie\Exception\RescueEvent;
use ICanBoogie\HTTP\RequestDispatcher\BeforeDispatchEvent;
use ICanBoogie\HTTP\RequestDispatcher\DispatchEvent;

/**
 * Dispatches HTTP requests.
 *
 * ## Events
 *
 * - `ICanBoogie\HTTP\RequestDispatcher::dispatch:before` of class {@link BeforeDispatchEvent}.
 * - `ICanBoogie\HTTP\RequestDispatcher::dispatch` of class {@link DispatchEvent}.
 * - `ICanBoogie\HTTP\RequestDispatcher::rescue` of class {@link ICanBoogie\Exception\RescueEvent}.
 */
class RequestDispatcher implements \ArrayAccess, \IteratorAggregate, Dispatcher
{
	/**
	 * The dispatchers called during the dispatching of the request.
	 *
	 * @var array
	 */
	protected $dispatchers = [];

	/**
	 * The weights of the dispatchers.
	 *
	 * @var array
	 */
	protected $dispatchers_weight = [];

	protected $dispatchers_order;

	/**
	 * Initializes the {@link $dispatchers} property.
	 *
	 * Dispatchers can be defined as callable or class name. If a dispatcher definition is not a
	 * callable it is used as class name to instantiate a dispatcher.
	 *
	 * @param array $dispatchers
	 */
	public function __construct(array $dispatchers = [])
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
		$response = $this->handle($request);

		if ($request->is_head && $response->body)
		{
			return new Response(null, $response->status, $response->headers);
		}

		return $response;
	}

	private function handle(Request $request)
	{
		try
		{
			return $this->dispatch($request);
		}
		catch (\Exception $e)
		{
			if ($e instanceof NotFound && $request->is_head)
			{
				return $this->handle_head($request);
			}

			return $this->rescue($e, $request);
		}
	}

	/**
	 * Trying to rescue a NotFound HEAD request using GET instead.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 */
	private function handle_head(Request $request)
	{
		$response = $this->handle($request->with([ 'is_get' => true ]));

		if ($response->content_length === null)
		{
			try
			{
				$response->content_length = strlen((string) $response->body);
			}
			catch (\Exception $e)
			{
				#
				# It's not that bad if we can't obtain the length of the body.
				#
			}
		}

		return $response;
	}

	/**
	 * Checks if the dispatcher is defined.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 *
	 * @return bool `true` if the dispatcher is defined, `false` otherwise.
	 */
	public function offsetExists($dispatcher_id)
	{
		return isset($this->dispatchers[$dispatcher_id]);
	}

	/**
	 * Returns a dispatcher.
	 *
	 * @param string $dispatcher_id The identifier of the dispatcher.
	 *
	 * @return mixed
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

			$this->dispatchers_order = \ICanBoogie\sort_by_weight($this->dispatchers, function($v, $k) use ($weights) {

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
	 * the dispatcher implements the {@link Dispatcher} interface then its
	 * {@link Dispatcher::rescue} method is invoked to rescue the exception, otherwise the
	 * exception is just re-thrown.
	 *
	 * {@link BeforeDispatchEvent} is fired before dispatchers are traversed. If a
	 * response is provided the dispatchers are skipped.
	 *
	 * {@link DispatchEvent} is fired before the response is returned. The event is
	 * fired event if the dispatchers did'nt return a response. It's the last chance to get one.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 *
	 * @throws \Exception If the dispatcher that raised an exception during dispatch doesn't implement
	 * {@link Dispatcher}.
	 * @throws NotFound when neither the events nor the dispatchers were able to provide
	 * a {@link Response}.
	 */
	protected function dispatch(Request $request)
	{
		$response = null;

		new BeforeDispatchEvent($this, $request, $response);

		if (!$response)
		{
			foreach ($this as $id => $dispatcher)
			{
				#
				# If the dispatcher is not a callable then it is considered as a class name, which
				# is used to instantiate a dispatcher.
				#

				if (!($dispatcher instanceof CallableDispatcher))
				{
					$this->dispatchers[$id] = $dispatcher = is_callable($dispatcher) ? new CallableDispatcher($dispatcher) : new $dispatcher;
				}

				$response = $this->dispatch_with_dispatcher($dispatcher, $request);

				if ($response) break;
			}
		}

		new DispatchEvent($this, $request, $response);

		if (!$response)
		{
			throw new NotFound;
		}

		return $response;
	}

	/**
	 * Dispatches the request using a dispatcher.
	 *
	 * @param Dispatcher $dispatcher
	 * @param Request $request
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	protected function dispatch_with_dispatcher(Dispatcher $dispatcher, Request $request)
	{
		try
		{
			$request->context->dispatcher = $dispatcher;

			$response = $dispatcher($request);
		}
		catch (\Exception $e)
		{
			$response = $dispatcher->rescue($e, $request);
		}

		$request->context->dispatcher = null;

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
		/* @var $response Response */
		$response = null;

		new RescueEvent($exception, $request, $response);

		if (!$response)
		{
			if ($exception instanceof ForceRedirect)
			{
				return new RedirectResponse($exception->location, $exception->getCode());
			}

			throw $exception;
		}

		$this->alter_response_with_exception($response, $exception);

		return $response;
	}

	/**
	 * Alters a response with an exception.
	 *
	 * The methods adds the `X-ICanBoogie-Rescued-Exception` header to the response, which
	 * describes the filename and the line where the exception occurred.
	 *
	 * @param Response $response
	 * @param \Exception $exception
	 */
	protected function alter_response_with_exception(Response $response, \Exception $exception)
	{
		$pathname = $exception->getFile();
		$root = $_SERVER['DOCUMENT_ROOT'];

		if ($root && strpos($pathname, $root) === 0)
		{
			$pathname = substr($pathname, strlen($root));
		}

		$response->headers['X-ICanBoogie-Rescued-Exception'] = $pathname . '@' . $exception->getLine();
	}
}
