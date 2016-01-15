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

/**
 * Exception thrown to force the redirect of the response.
 *
 * @property-read string $location The location of the redirect.
 */
class ForceRedirect extends \Exception implements Exception
{
	use AccessorTrait;

	private $location;

	protected function get_location()
	{
		return $this->location;
	}

	public function __construct($location, $code = Status::FOUND, \Exception $previous = null)
	{
		$this->location = $location;

		parent::__construct($this->format_message($location), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param string $location
	 *
	 * @return string
	 */
	protected function format_message($location)
	{
		return \ICanBoogie\format("Location: %location", [ 'location' => $location ]);
	}
}
