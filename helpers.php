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
 * Dispatches a request.
 *
 * The request is dispatched by the dispatcher returned by the {@link get_dispatcher()} function.
 *
 * @param Request $request
 *
 * @return Response
 */
function dispatch(Request $request)
{
	$dispatcher = get_dispatcher();

	return $dispatcher($request);
}

/**
 * Returns a shared request dispatcher.
 *
 * @return Dispatcher
 */
function get_dispatcher()
{
	if (!DispatcherProvider::defined())
	{
		DispatcherProvider::define(new ProvideDispatcher);
	}

	return DispatcherProvider::provide();
}

/**
 * Returns the initial request.
 *
 * The initial request is created once from the `$_SERVER` array.
 *
 * @return Request
 */
function get_initial_request()
{
	static $initial_request;

	if (!$initial_request)
	{
		$initial_request = Request::from($_SERVER);
	}

	return $initial_request;
}
