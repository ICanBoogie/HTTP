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

use Closure;

/**
 * Turns any Closure into a Responder.
 */
final class ResponderClosure implements Responder
{
	public function __construct(private Closure $closure)
	{
	}

	public function respond(Request $request): Response
	{
		return ($this->closure)($request);
	}
}
