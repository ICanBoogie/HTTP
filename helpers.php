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
 * @param Request $request
 *
 * @return Response
 */
function dispatch(Request $request)
{
	return Helpers::dispatch($request);
}

/**
 * Returns shared request dispatcher.
 *
 * @return Dispatcher
 */
function get_dispatcher()
{
	return Helpers::get_dispatcher();
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
	return Helpers::get_initial_request();
}
