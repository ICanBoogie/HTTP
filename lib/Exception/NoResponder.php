<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Exception;

use ICanBoogie\HTTP\Exception;
use LogicException;

/**
 * Thrown when there's no responder available for a request.
 */
class NoResponder extends LogicException implements Exception
{
}
