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
 * Exception thrown in attempt to obtain a dispatcher that is not defined.
 *
 * @property-read string $dispatcher_id The identifier of the dispatcher.
 */
class DispatcherNotDefined extends \LogicException implements Exception
{
	use GetterTrait;

	private $dispatcher_id;

	protected function get_dispatcher_id()
	{
		return $this->dispatcher_id;
	}

	public function __construct($dispatcher_id, $message=null, $code=500, \Exception $previous=null)
	{
		$this->dispatcher_id = $dispatcher_id;

		if (!$message)
		{
			$message = \ICanBoogie\format("The dispatcher %dispatcher_id is not defined.", [

				'dispatcher_id' => $dispatcher_id

			]);
		}

		parent::__construct($message, $code, $previous);
	}
}
