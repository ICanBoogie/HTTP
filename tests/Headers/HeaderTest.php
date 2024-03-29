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

use ICanBoogie\FormattedString;
use ICanBoogie\HTTP\Headers\Header;
use ICanBoogie\HTTP\Headers\HeaderParameter;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use PHPUnit\Framework\TestCase;

final class HeaderTest extends TestCase
{
    public function test_value()
    {
        $a = new A();
        $this->assertNull($a->value);
        $this->assertEquals('', (string) $a);

        $a->value = 'value';
        $this->assertEquals('value', (string) $a);
    }

    public function test_value_alias()
    {
        $a = new A();

        $this->assertNull($a->type);
        $this->assertNull($a->value);

        $a->type = 'inline';
        $this->assertEquals('inline', $a->type);
        $this->assertEquals('inline', $a->value);

        $a->value = 'attachment';
        $this->assertEquals('attachment', $a->type);
        $this->assertEquals('attachment', $a->value);
    }

    public function test_attributes()
    {
        $a = new A();
        $this->assertInstanceOf(HeaderParameter::class, $a['p']);
        $this->assertNotInstanceOf(HeaderParameter::class, $a->p);

        $a->type = 'inline';
        $this->assertEquals('inline', (string) $a);

        $a->p = 'madonna.mp3';
        $this->assertEquals('inline; p=madonna.mp3', (string) $a);
        unset($a['p']);
        $this->assertNull($a->p);
        $this->assertInstanceOf(HeaderParameter::class, $a['p']);
        $this->assertNotInstanceOf(HeaderParameter::class, $a->p);
        $this->assertEquals('inline', (string) $a);

        $a->p = 'madonna.mp3';
        $this->assertEquals('inline; p=madonna.mp3', (string) $a);
        $a['p'] = 'madonna.ogg';
        $this->assertEquals('inline; p=madonna.ogg', (string) $a);
        unset($a->p);
        $this->assertNull($a->p);
        $this->assertInstanceOf(HeaderParameter::class, $a['p']);
        $this->assertNotInstanceOf(HeaderParameter::class, $a->p);
        $this->assertEquals('inline', (string) $a);
    }

    public function test_unsetting_attribute()
    {
        $a = new A();
        $expected = uniqid();
        $a->p = $expected;
        $this->assertEquals($expected, $a->p);
        $this->assertEquals($expected, $a['p']->value);

        unset($a->p);
        $this->assertNull($a->p);
        $this->assertTrue(isset($a['p']));
        $this->assertNull($a['p']->value);

        unset($a->undefined);
    }

    public function test_ignore_unrecognized_parameter()
    {
        $a = A::from("123; p=test.txt; singer=madonna");

        $this->assertEquals('123', $a->value);
        $this->assertEquals('test.txt', $a->p);
        $this->assertEquals('123; p=test.txt', (string) $a);
    }

    public function test_setting_unsupported_attribute_using_a_property_should_throw_an_exception()
    {
        $a = new A();
        $this->expectException(PropertyNotDefined::class);
        $a->b = true;
    }

    public function test_setting_unsupported_attribute_using_an_offset_should_throw_an_exception()
    {
        $a = new A();
        $this->expectException(OffsetNotDefined::class);
        $a['b'] = true;
    }

    public function test_getting_unsupported_attribute_using_a_property_should_throw_an_exception()
    {
        $a = new A();
        $this->expectException(PropertyNotDefined::class);
        $b = $a->b;
    }

    public function test_getting_unsupported_attribute_using_an_offset_should_throw_an_exception()
    {
        $a = new A();
        $this->expectException(OffsetNotDefined::class);
        $b = $a['b'];
    }

    public function test_from()
    {
        $h = new A();
        $this->assertSame($h, Header::from($h));
    }

    public function test_from_to_string()
    {
        $h = A::from(new FormattedString('madonna; p=music'));

        $this->assertInstanceOf(Header::class, $h);
        $this->assertEquals("madonna", $h->value);
        $this->assertEquals("music", $h->p);
    }
}

namespace Test\ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers\Header;
use ICanBoogie\HTTP\Headers\HeaderParameter;

class A extends Header
{
    public const VALUE_ALIAS = 'type';

    public function __construct($value = null, array $attributes = [])
    {
        $this->parameters['p'] = new HeaderParameter('p');

        parent::__construct($value, $attributes);
    }
}
