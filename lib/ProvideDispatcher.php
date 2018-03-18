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

/**
 * Basic dispatcher provider.
 */
class ProvideDispatcher
{
    /**
     * @var RequestDispatcher
     */
	private $dispatcher;

	public function __invoke(): RequestDispatcher
	{
		$dispatcher = &$this->dispatcher;

		if ($dispatcher)
		{
			return $dispatcher;
		}

		$dispatcher = $this->create();

		new RequestDispatcher\AlterEvent($dispatcher);

		return $dispatcher;
	}

	/**
	 * The method can be overrode to provide an initialized dispatcher.
	 */
	protected function create(): RequestDispatcher
	{
		return new RequestDispatcher;
	}
}
