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
	 */
	public function test_properties($expect, $properties)
	{
		$f = new CacheControl;

		foreach ($properties as $property => $value)
		{
			$f->$property = $value;
		}

		$this->assertEquals($expect, (string) $f);
	}

	/**
	 * @dataProvider provide_properties
	 */
	public function test_from($from, $properties)
	{
		$f = CacheControl::from($from);

		foreach ($properties as $property => $value)
		{
			if ($property == 'cacheable' && $value === false) // exception
			{
				continue;
			}

			$this->assertEquals($value, $f->$property);
		}
	}

	public function provide_properties()
	{
		return array
		(
			array('', array()),

			array('public', array('cacheable' => 'public')),
			array('private', array('cacheable' => 'private')),
			array('no-cache', array('cacheable' => 'no-cache')),
			array('no-cache', array('cacheable' => false)),
			array('', array('cacheable' => null)),

			array('no-store', array('no_store' => true)),
			array('', array('no_store' => false)),

			array('no-transform', array('no_transform' => true)),
			array('', array('no_transform' => false)),

			array('only-if-cached', array('only_if_cached' => true)),
			array('', array('only_if_cached' => false)),

			array('must-revalidate', array('must_revalidate' => true)),
			array('', array('must_revalidate' => false)),

			array('proxy-revalidate', array('proxy_revalidate' => true)),
			array('', array('proxy_revalidate' => false)),

			array('max-age=3600', array('max_age' => 3600)),
			array('max-age=0', array('max_age' => 0)),
			array('', array('max_age' => null)),

			array('s-maxage=3600', array('s_maxage' => 3600)),
			array('s-maxage=0', array('s_maxage' => 0)),
			array('', array('s_maxage' => null)),

			array('max-stale=3600', array('max_stale' => 3600)),
			array('max-stale=0', array('max_stale' => 0)),
			array('', array('max_stale' => null)),

			array('min-fresh=3600', array('min_fresh' => 3600)),
			array('min-fresh=0', array('min_fresh' => 0)),
			array('', array('min_fresh' => null)),

			array('public, no-store, max-age=0', array('cacheable' => 'public', 'no_store' => true, 'must_revalidate' => false, 'max_age' => 0))
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_set_invalid_cacheable()
	{
		$f = new CacheControl;
		$f->cacheable = 'madonna';
	}
}