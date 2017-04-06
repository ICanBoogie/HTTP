<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Exception\ClientError;

use ICanBoogie\HTTP\Exception\ClientError;

/**
 * Exception interface for "415 Unsupported Media Type".
 */
interface UnsupportedMediaType extends ClientError
{
	const CODE = 415;
	const MESSAGE = "Unsupported Media Type";
}
