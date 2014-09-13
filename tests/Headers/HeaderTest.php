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

use ICanBoogie\HTTP\Headers\HeaderTest\A;

class HeaderTest extends \PHPUnit_Framework_TestCase
{
	public function test_value()
	{
		$a = new A;
		$this->assertNull($a->value);
		$this->assertEquals('', (string) $a);

		$a->value = 'value';
		$this->assertEquals('value', (string) $a);
	}

	public function test_value_alias()
	{
		$a = new A;

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
		$a = new A;
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a['p']);
		$this->assertNotInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a->p);

		$a->type = 'inline';
		$this->assertEquals('inline', (string) $a);

		$a->p = 'madonna.mp3';
		$this->assertEquals('inline; p=madonna.mp3', (string) $a);
		unset($a['p']);
		$this->assertNull($a->p);
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a['p']);
		$this->assertNotInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a->p);
		$this->assertEquals('inline', (string) $a);

		$a->p = 'madonna.mp3';
		$this->assertEquals('inline; p=madonna.mp3', (string) $a);
		unset($a->p);
		$this->assertNull($a->p);
		$this->assertInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a['p']);
		$this->assertNotInstanceOf('ICanBoogie\HTTP\Headers\HeaderParameter', $a->p);
		$this->assertEquals('inline', (string) $a);
	}

	public function test_ignore_unrecognized_parameter()
	{
		$a = A::from("123; p=test.txt; singer=madonna");

		$this->assertEquals('123', $a->value);
		$this->assertEquals('test.txt', $a->p);
		$this->assertEquals('123; p=test.txt', (string) $a);
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotDefined
	 */
	public function test_setting_unsupported_attribute_using_a_property_should_throw_an_exception()
	{
		$a = new A;
		$a->b = true;
	}

	/**
	 * @expectedException ICanBoogie\OffsetNotDefined
	 */
	public function test_setting_unsupported_attribute_using_an_offset_should_throw_an_exception()
	{
		$a = new A;
		$a['b'] = true;
	}

	/**
	 * @expectedException ICanBoogie\PropertyNotDefined
	 */
	public function test_getting_unsupported_attribute_using_a_property_should_throw_an_exception()
	{
		$a = new A;
		$b = $a->b;
	}

	/**
	 * @expectedException ICanBoogie\OffsetNotDefined
	 */
	public function test_getting_unsupported_attribute_using_an_offset_should_throw_an_exception()
	{
		$a = new A;
		$b = $a['b'];
	}
}

namespace ICanBoogie\HTTP\Headers\HeaderTest;

use ICanBoogie\HTTP\Headers\HeaderParameter;

class A extends \ICanBoogie\HTTP\Headers\Header
{
	const VALUE_ALIAS = 'type';

	public function __construct($value=null, array $parameters=[])
	{
		$this->parameters['p'] = new HeaderParameter('p');

		parent::__construct($value, $parameters);
	}
}