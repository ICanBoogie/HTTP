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

use ICanBoogie\GetterTrait;

/**
 * Exception thrown to force the redirect of the response.
 *
 * @property-read string $location The location of the redirect.
 */
class ForceRedirect extends HTTPError
{
	use GetterTrait;

	private $location;

	protected function get_location()
	{
		return $this->location;
	}

	public function __construct($location, $code=302, \Exception $previous=null)
	{
		$this->location = $location;

		parent::__construct(\ICanBoogie\format("Location: %location", [ 'location' => $location ]), $code, $previous);
	}
}