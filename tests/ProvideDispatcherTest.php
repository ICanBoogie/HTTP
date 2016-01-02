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

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;

class ProvideDispatcherTest extends \PHPUnit_Framework_TestCase
{
	private $events;

	public function setUp()
	{
		$this->events = new EventCollection;

		EventCollectionProvider::define(function() {

			return $this->events;

		});
	}

	public function test_invoke()
	{
		$called_event = 0;

		$this->events->attach(function(RequestDispatcher\AlterEvent $event, RequestDispatcher $target) use (&$called_event) {

			$called_event++;

		});

		$provide = new ProvideDispatcher;
		$dispatcher = $provide();

		$this->assertInstanceOf(RequestDispatcher::class, $dispatcher);
		$this->assertEquals(1, $called_event);
		$this->assertSame($dispatcher, $provide());
		$this->assertEquals(1, $called_event);
	}
}
