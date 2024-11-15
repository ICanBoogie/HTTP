<?php

namespace ICanBoogie\HTTP;

use Throwable;

/**
 * Exception thrown to force the redirect of the response.
 *
 * @property-read string $location The location of the redirect.
 */
class ForceRedirect extends \Exception implements Exception
{
    public function __construct(
        public readonly string $location,
        int $code = ResponseStatus::STATUS_FOUND,
        ?Throwable $previous = null
    ) {
        parent::__construct($this->format_message($location), $code, $previous);
    }

    private function format_message(string $location): string
    {
        return "Location: $location";
    }
}
