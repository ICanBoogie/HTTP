<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP\Exception;

use ICanBoogie\HTTP\Exception;
use LogicException;

/**
 * Thrown when there is no responder available for a request.
 */
class NoResponder extends LogicException implements Exception
{
}
