<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP\Headers;

class CacheControlTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_properties
	 *
	 * @param string $expect
	 * @param array $properties
	 */
	public function test_properties($expect, $properties)
	{
		$cache_control = new CacheControl;

		foreach ($properties as $property => $value)
		{
			$cache_control->$property = $value;
		}

		$this->assertEquals($expect, (string) $cache_control);
	}

	/**
	 * @dataProvider provide_properties
	 *
	 * @param string $from
	 * @param array $properties
	 */
	public function test_from($from, $properties)
	{
		$cache_control = CacheControl::from($from);

		foreach ($properties as $property => $value)
		{
			if ($property == 'cacheable' && $value === false) // exception
			{
				continue;
			}

			$this->assertEquals($value, $cache_control->$property);
		}
	}

	public function provide_properties()
	{
		return [

			[ '', [] ],

			[ 'public', [ 'cacheable' => 'public' ] ],
			[ 'private', [ 'cacheable' => 'private'] ],
			[ 'no-cache', [ 'cacheable' => 'no-cache' ] ],
			[ 'no-cache', [ 'cacheable' => false ] ],
			[ '', [ 'cacheable' => null ] ],

			[ 'no-store', [  'no_store' => true ] ],
			[ '', [ 'no_store' => false ] ],

			[ 'no-transform', [ 'no_transform' => true ] ],
			[ '', [ 'no_transform' => false ] ],

			[ 'only-if-cached', [ 'only_if_cached' => true ] ],
			[ '', [ 'only_if_cached' => false ] ],

			[ 'must-revalidate', [ 'must_revalidate' => true ] ],
			[ '', [ 'must_revalidate' => false ] ],

			[ 'proxy-revalidate', [ 'proxy_revalidate' => true ] ],
			[ '', [ 'proxy_revalidate' => false ] ],

			[ 'max-age=3600', [ 'max_age' => 3600 ] ],
			[ 'max-age=0', [ 'max_age' => 0 ] ],
			[ '', [ 'max_age' => null ] ],

			[ 's-maxage=3600', [ 's_maxage' => 3600 ] ],
			[ 's-maxage=0', [ 's_maxage' => 0 ] ],
			[ '', [ 's_maxage' => null ] ],

			[ 'max-stale=3600', [ 'max_stale' => 3600 ] ],
			[ 'max-stale=0', [ 'max_stale' => 0 ] ],
			[ '', [ 'max_stale' => null ] ],

			[ 'min-fresh=3600', [ 'min_fresh' => 3600 ] ],
			[ 'min-fresh=0', [ 'min_fresh' => 0 ] ],
			[ '', [ 'min_fresh' => null ] ],

			[ 'public, no-store, max-age=0', [ 'cacheable' => 'public', 'no_store' => true, 'must_revalidate' => false, 'max_age' => 0 ] ],

		];
	}

	public function test_from_same()
	{
		$instance = CacheControl::from('public, no-store, max-age=0');

		$this->assertInstanceOf(CacheControl::class, $instance);
		$this->assertSame($instance, CacheControl::from($instance));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function test_set_invalid_cacheable()
	{
		$cache_control = new CacheControl;
		$cache_control->cacheable = 'madonna';
	}

	public function test_extensions()
	{
		$cache_control = new CacheControl("public, ext1=one, ext2=two");
		$this->assertEquals([ 'ext1' => "one", 'ext2' => "two" ], $cache_control->extensions);
	}
}
