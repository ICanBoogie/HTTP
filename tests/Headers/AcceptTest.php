<?php

namespace ICanBoogie\HTTP\Headers;

class AcceptTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_from
	 *
	 * @param string $definition
	 */
	public function test_from($definition)
	{
		$header = Accept::from($definition);

		var_dump($header);
	}

	public function provide_test_from()
	{
		return [

			[ "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" ]

		];
	}
}
