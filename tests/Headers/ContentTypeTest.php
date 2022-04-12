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

class ContentTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider_from
     */
    public function test_from($source, $values)
    {
        /* @var $h ContentType */
        $h = ContentType::from($source);

        $this->assertInstanceOf(ContentType::class, $h);
        $this->assertEquals($values[0], $h->value);
        $this->assertEquals($values[0], $h->type);
        $this->assertEquals($values[1], $h->charset);

        $this->assertEquals($source, (string) $h);
    }

    public function provider_from()
    {
        return [

            [ 'text/html', [

                'text/html',
                null

            ] ],

            [ 'text/html; charset=utf-8', [

                'text/html',
                'utf-8'

            ] ],

            [ 'text/plain; charset=iso-8859-1', [

                'text/plain',
                'iso-8859-1'

            ] ]
        ];
    }

    public function test_attributes()
    {
        $content_type = new ContentType();

        $this->assertNull($content_type->type);
        $this->assertNull($content_type->charset);

        $content_type->type = 'text/html';
        $this->assertEquals('text/html', (string) $content_type);

        $content_type->charset = 'utf-8';
        $this->assertEquals('text/html; charset=utf-8', (string) $content_type);

        # if there is no `type` the string must be empty
        $content_type->type = null;
        $this->assertEquals('', (string) $content_type);
    }
}
