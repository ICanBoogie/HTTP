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

class HelpersTest extends \PHPUnit_Framework_TestCase
{
	public function test_dispatcher()
	{
		$dispatcher = get_dispatcher();
		$this->assertInstanceOf(RequestDispatcher::class, $dispatcher);
		$this->assertSame($dispatcher, get_dispatcher());

		$other_dispatcher = $this->getMock(Dispatcher::class);

		DispatcherProvider::define(function() use ($other_dispatcher) {

			return $other_dispatcher;

		});

		$this->assertSame($other_dispatcher, get_dispatcher());

		DispatcherProvider::undefine();
	}

	public function test_array_normalize()
	{
		$params = [

			'a' => '',
			'b' => '  ',
			'c' => null,
			'd' => 0,
			'e' => '0',
			'f' => [

				'a' => '',
				'b' => '   ',
				'c' => null,
				'd' => 0,
				'e' => '0'

			]

		];

		array_normalize($params);

		$this->assertSame([

			'a' => null,
			'b' => null,
			'c' => null,
			'd' => 0,
			'e' => '0',
			'f' => [

				'a' => null,
				'b' => null,
				'c' => null,
				'd' => 0,
				'e' => '0'

			]

		], $params);
	}

	public function test_to_array_flatten()
	{
		$params = [

			'a' => '',
			'b' => [

				'ba' => [

					'baa' => 'baa',
					'bab' => [

						'baba' => 'baba'

					]

				],

				'bb' => 'bb',
				'bc' => '',

			],

			'c' => [ 'ca' => ['caa' => [ 'caaa' => 'caaa', 'caab' => null ] ] ],

			'd' => [ [ [ [ 'b' ] ] ] ]

		];

		array_flatten($params);
		array_normalize($params);

		$this->assertSame([

			'a' => null,
			'b[bb]' => "bb",
			'b[bc]' => null,
			'b[ba][baa]' => "baa",
			'b[ba][bab][baba]' => "baba",
			'c[ca][caa][caaa]' => "caaa",
			'c[ca][caa][caab]' => null,
			'd[0][0][0][0]' => "b",

		], $params);
	}
}
