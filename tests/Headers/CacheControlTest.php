<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers\CacheControl;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheControlTest extends TestCase
{
    /**
     * @dataProvider provide_properties
     *
     * @param string $expect
     * @param array $properties
     */
    public function test_properties($expect, $properties)
    {
        $cache_control = new CacheControl();

        foreach ($properties as $property => $value) {
            $cache_control->$property = $value;
        }

        $this->assertEquals($expect, (string) $cache_control);
    }

    /**
     * @dataProvider provide_properties
     */
    public function test_from(string $from, array $properties)
    {
        $cache_control = CacheControl::from($from);

        foreach ($properties as $property => $value) {
            $this->assertEquals($value, $cache_control->$property);
        }
    }

    public function provide_properties(): array
    {
        return [

            [ 'public', [ 'cacheable' => 'public' ] ],
            [ 'private', [ 'cacheable' => 'private'] ],
            [ 'no-cache', [ 'cacheable' => 'no-cache' ] ],
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

    public function test_from_same(): void
    {
        $instance = CacheControl::from('public, no-store, max-age=0');

        $this->assertInstanceOf(CacheControl::class, $instance);
        $this->assertSame($instance, CacheControl::from($instance));
    }

    public function test_set_invalid_cacheable(): void
    {
        $cache_control = new CacheControl();
        $this->expectException(InvalidArgumentException::class);
        $cache_control->cacheable = 'madonna';
    }

    public function test_extensions(): void
    {
        $cache_control = new CacheControl("public, ext1=one, ext2=two");
        $this->assertEquals([ 'ext1' => "one", 'ext2' => "two" ], $cache_control->extensions);
    }
}
