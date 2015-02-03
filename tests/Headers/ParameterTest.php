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

class HeaderParameterTest extends \PHPUnit_Framework_TestCase
{
	public function test_is_token()
	{
		$this->assertTrue(HeaderParameter::is_token("token"));
		$this->assertFalse(HeaderParameter::is_token("token!?"));
		$this->assertFalse(HeaderParameter::is_token("token\n"));
		$this->assertFalse(HeaderParameter::is_token("token\t"));
		$this->assertFalse(HeaderParameter::is_token("token or not token?"));
		$this->assertFalse(HeaderParameter::is_token("\x13token"));
		$this->assertFalse(HeaderParameter::is_token("\x00token"));
		$this->assertFalse(HeaderParameter::is_token("token\x7f"));
		$this->assertFalse(HeaderParameter::is_token("tökèn"));
		$this->assertFalse(HeaderParameter::is_token(mb_convert_encoding("tökèn", 'ISO-8859-1')));
	}

	/**
	 * @depends test_is_token
	 */
	public function test_render_with_token()
	{
		$v = 'token';
		$p = new HeaderParameter('title', $v);
		$this->assertEquals($v, (string) $p);
		$this->assertEquals('title=token', $p->render());
	}

	/**
	 * @depends test_is_token
	 */
	public function test_render_with_quoted_string()
	{
		$v = 'quoted string';
		$p = new HeaderParameter('title', $v);
		$this->assertEquals($v, (string) $p);
		$this->assertEquals('title="quoted string"', $p->render());
	}

	/**
	 * @depends test_is_token
	 */
	public function test_render_with_quoted_string_with_double_quote()
	{
		$v = 'quoted"string';
		$p = new HeaderParameter('title', $v);
		$this->assertEquals($v, (string) $p);
		$this->assertEquals('title="quotedstring"; title*=ASCII\'\'quoted%22string', $p->render());
	}

	/**
	 * @depends test_is_token
	 */
	public function test_render_with_utf_string()
	{
		$v = "L'été est là";
		$p = new HeaderParameter('title', $v);
		$this->assertEquals($v, (string) $p);
		$this->assertEquals("title=\"L'ete est la\"; title*=UTF-8''L%27%C3%A9t%C3%A9%20est%20l%C3%A0", $p->render());
	}

	/**
	 * @depends test_is_token
	 */
	public function test_from()
	{
		$s = 'title=Economy';
		$p = HeaderParameter::from($s);
		$this->assertEquals('title', $p->attribute);
		$this->assertEquals('ASCII', $p->charset);
		$this->assertNull($p->language);
		$this->assertEquals("Economy", $p->value);

		$s = 'title="US-$ rates"';
		$p = HeaderParameter::from($s);
		$this->assertEquals('title', $p->attribute);
		$this->assertEquals('ASCII', $p->charset);
		$this->assertNull($p->language);
		$this->assertEquals('US-$ rates', $p->value);

		$s = 'title*=iso-8859-1\'en\'%A3%20rates';
		$p = HeaderParameter::from($s);
		$this->assertEquals('title', $p->attribute);
		$this->assertEquals('UTF-8', $p->charset);
		$this->assertEquals("en", $p->language);
		$this->assertEquals("£ rates", $p->value);

		$s = 'title*=UTF-8\'\'%c2%a3%20and%20%e2%82%ac%20rates';
		$p = HeaderParameter::from($s);
		$this->assertEquals('title', $p->attribute);
		$this->assertEquals('UTF-8', $p->charset);
		$this->assertNull($p->language);
		$this->assertEquals("£ and € rates", $p->value);

		$this->assertSame($p, HeaderParameter::from($p));
	}
}
