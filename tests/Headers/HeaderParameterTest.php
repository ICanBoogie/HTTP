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

class HeaderParameterTest extends \PHPUnit\Framework\TestCase
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
        $value = 'token';
        $parameter = new HeaderParameter('title', $value);
        $this->assertEquals($value, (string) $parameter);
        $this->assertEquals('title=token', $parameter->render());
    }

    /**
     * @depends test_is_token
     */
    public function test_render_with_quoted_string()
    {
        $value = 'quoted string';
        $parameter = new HeaderParameter('title', $value);
        $this->assertEquals($value, (string) $parameter);
        $this->assertEquals('title="quoted string"', $parameter->render());
    }

    /**
     * @depends test_is_token
     */
    public function test_render_with_quoted_string_with_double_quote()
    {
        $value = 'quoted"string';
        $parameter = new HeaderParameter('title', $value);
        $this->assertEquals($value, (string) $parameter);
        $this->assertEquals('title="quotedstring"; title*=ASCII\'\'quoted%22string', $parameter->render());
    }

    /**
     * @depends test_is_token
     */
    public function test_render_with_utf_string()
    {
        $value = "L'été est là";
        $parameter = new HeaderParameter('title', $value);
        $this->assertEquals($value, (string) $parameter);
        $this->assertEquals("title=\"L'ete est la\"; title*=UTF-8''L%27%C3%A9t%C3%A9%20est%20l%C3%A0", $parameter->render());
    }

    /**
     * @depends test_is_token
     */
    public function test_from()
    {
        $str = 'title=Economy';
        $parameter = HeaderParameter::from($str);
        $this->assertEquals('title', $parameter->attribute);
        $this->assertEquals('ASCII', $parameter->charset);
        $this->assertNull($parameter->language);
        $this->assertEquals("Economy", $parameter->value);

        $str = 'title="US-$ rates"';
        $parameter = HeaderParameter::from($str);
        $this->assertEquals('title', $parameter->attribute);
        $this->assertEquals('ASCII', $parameter->charset);
        $this->assertNull($parameter->language);
        $this->assertEquals('US-$ rates', $parameter->value);

        $str = 'title*=iso-8859-1\'en\'%A3%20rates';
        $parameter = HeaderParameter::from($str);
        $this->assertEquals('title', $parameter->attribute);
        $this->assertEquals('UTF-8', $parameter->charset);
        $this->assertEquals("en", $parameter->language);
        $this->assertEquals("£ rates", $parameter->value);

        $str = 'title*=UTF-8\'\'%c2%a3%20and%20%e2%82%ac%20rates';
        $parameter = HeaderParameter::from($str);
        $this->assertEquals('title', $parameter->attribute);
        $this->assertEquals('UTF-8', $parameter->charset);
        $this->assertNull($parameter->language);
        $this->assertEquals("£ and € rates", $parameter->value);

        $this->assertSame($parameter, HeaderParameter::from($parameter));
    }
}
