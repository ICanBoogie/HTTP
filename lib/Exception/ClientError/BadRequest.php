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
 * Exception interface for "400 Bad Request".
 */
interface BadRequest extends ClientError
{
	const CODE = 400;
	const MESSAGE = "Bad Request";
}
