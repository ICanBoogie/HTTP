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

use ICanBoogie\Accessor\AccessorTrait;

use function ICanBoogie\format;

/**
 * Exception thrown to force the redirect of the response.
 *
 * @property-read string $location The location of the redirect.
 */
class ForceRedirect extends \Exception implements Exception
{
    use AccessorTrait;

    /**
     * @var string
     */
    private $location;

    protected function get_location(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $location, int $code = Status::FOUND, \Throwable $previous = null)
    {
        $this->location = $location;

        parent::__construct($this->format_message($location), $code, $previous);
    }

    private function format_message(string $location): string
    {
        return format("Location: %location", [ 'location' => $location ]);
    }
}
