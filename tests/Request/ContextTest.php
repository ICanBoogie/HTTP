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
use ICanBoogie\Prototype;

class ContextTest extends \PHPUnit\Framework\TestCase
{
	public function test_get_request()
	{
		$request = Request::from('/');
		$context = new Context($request);
		$this->assertSame($request, $context->request);
	}

	public function test_set_dispatcher()
	{
		$context = new Context(Request::from('/'));
		$dispatcher = new RequestDispatcher;
		$context->dispatcher = $dispatcher;
		$this->assertSame($dispatcher, $context->dispatcher);
	}

	public function test_prototype()
	{
		$property = 'property' . uniqid();
		$value = uniqid();
		$invoked = 0;

		$context = new Context(Request::from('/'));
		$this->assertFalse(isset($context[$property]));

		Prototype::from(Context::class)["lazy_get_$property"] = function() use ($value, &$invoked) {

			$invoked++;

			return $value;

		};

		$this->assertSame($value, $context->$property);
		$this->assertSame($value, $context[$property]);
		$this->assertTrue(isset($context[$property]));
		$this->assertEquals(1, $invoked);

		unset($context[$property]);

		$this->assertSame($value, $context->$property);
		$this->assertSame($value, $context[$property]);

		$this->assertEquals(2, $invoked);

		$value = uniqid();

		$context[$property] = $value;

		$this->assertSame($value, $context[$property]);
	}
}
