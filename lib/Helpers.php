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
 * Patchable helpers of the HTTP package.
 *
 * The following helpers can be patched:
 *
 * - {@link dispatch}
 * - {@link get_dispatcher}
 */
class Helpers
{
	static private $jumptable = [

		'dispatch' =>            [ __CLASS__, 'dispatch' ],
		'get_dispatcher' =>      [ __CLASS__, 'get_dispatcher' ],
		'get_initial_request' => [ __CLASS__, 'get_initial_request' ]

	];

	/**
	 * Calls the callback of a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param array $arguments Arguments.
	 *
	 * @return mixed
	 */
	static public function __callstatic($name, array $arguments)
	{
		return call_user_func_array(self::$jumptable[$name], $arguments);
	}

	/**
	 * Patches a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param collable $callback Callback.
	 *
	 * @throws \RuntimeException in attempt to patch an undefined function.
	 */
	static public function patch($name, $callback)
	{
		if (empty(self::$jumptable[$name]))
		{
			throw new \RuntimeException("Undefined patchable: $name.");
		}

		self::$jumptable[$name] = $callback;
	}

	/*
	 * Fallbacks
	 */

	/**
	 * Fallback for the {@link get_dispatcher()} function.
	 */
	static private function get_dispatcher()
	{
		static $dispatcher;

		if (!$dispatcher)
		{
			$dispatcher = new Dispatcher;
		}

		return $dispatcher;
	}

	/**
	 * Fallback for the {@link dispatch()} function.
	 */
	static private function dispatch(Request $request)
	{
		$dispatcher = get_dispatcher();

		return $dispatcher($request);
	}

	/**
	 * Fallback for the {@link get_initial_request()} function.
	 */
	static private function get_initial_request()
	{
		static $request;

		if (!$request)
		{
			$request = Request::from($_SERVER);
		}

		return $request;
	}
}