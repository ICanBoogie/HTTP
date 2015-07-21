<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Request;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\Request;

class ContextTest extends \PHPUnit_Framework_TestCase
{
	public function test_get_request()
	{
		$r = Request::from('/');
		$c = new Context($r);
		$this->assertSame($r, $c->request);
	}

	public function test_set_dispatcher()
	{
		$c = new Context(Request::from('/'));
		$dispatcher = new RequestDispatcher;
		$c->dispatcher = $dispatcher;
		$this->assertSame($dispatcher, $c->dispatcher);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_set_dispatcher_invalid()
	{
		$c = new Context(Request::from('/'));
		$c->dispatcher = new \StdClass;
	}
}
