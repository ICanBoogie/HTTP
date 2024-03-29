<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP;

use DateTimeZone;
use Exception;
use ICanBoogie\DateTime;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Headers\Date;
use ICanBoogie\HTTP\Headers\Date as DateHeader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function uniqid;

final class HeadersTest extends TestCase
{
    public function testDateTimeFromDateTime()
    {
        $datetime = new \DateTime();
        $headers_datetime = new DateHeader($datetime);
        $datetime->setTimezone(new DateTimeZone('GMT'));

        $this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT', (string) $headers_datetime);
    }

    /**
     * @throws Exception
     */
    public function testDateTimeFromDateTimeString()
    {
        $datetime = new \DateTime('now', new DateTimeZone('GMT'));

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) new DateHeader($datetime->format('D, d M Y H:i:s P'))
        );

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) new DateHeader($datetime->format('D, d M Y H:i:s'))
        );

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) new DateHeader($datetime->format('Y-m-d H:i:s'))
        );
    }

    public function test_cache_control()
    {
        $headers = new Headers();
        $this->assertInstanceOf(Headers\CacheControl::class, $headers['Cache-Control']);
        $this->assertSame($headers['Cache-Control'], $headers->cache_control);
        $headers['Cache-Control'] = 'public, max-age=3600, no-transform';
        $this->assertInstanceOf(Headers\CacheControl::class, $headers['Cache-Control']);
        $this->assertEquals('public', $headers->cache_control->cacheable);
        $this->assertEquals('3600', $headers->cache_control->max_age);
        $headers->cache_control->modify('public, max-age=3600, no-transform');
        $this->assertInstanceOf(Headers\CacheControl::class, $headers['Cache-Control']);
        $this->assertEquals('public', $headers->cache_control->cacheable);
        $this->assertEquals('3600', $headers->cache_control->max_age);
        $this->assertTrue($headers->cache_control->no_transform);
    }

    public function test_content_disposition()
    {
        $headers = new Headers();
        $this->assertInstanceOf(Headers\ContentDisposition::class, $headers['Content-Disposition']);
        $headers['Content-Disposition'] = "attachment; filename=test.txt";
        $this->assertInstanceOf(Headers\ContentDisposition::class, $headers['Content-Disposition']);
        $this->assertEquals('attachment', $headers->content_disposition->type);
        $this->assertEquals('test.txt', $headers->content_disposition->filename);
    }

    public function test_content_length()
    {
        $headers = new Headers();
        $this->assertNull($headers['Content-Length']);
        $headers['Content-Length'] = 123;
        $this->assertEquals(123, $headers['Content-Length']);

        $headers = new Headers();
        $this->assertNull($headers->content_length);
        $headers->content_length = 123;
        $this->assertEquals(123, $headers->content_length);
    }

    public function test_content_type()
    {
        $headers = new Headers();
        $this->assertInstanceOf(Headers\ContentType::class, $headers['Content-Type']);
        $headers['Content-Type'] = 'text/plain; charset=iso-8859-1';
        $this->assertInstanceOf(Headers\ContentType::class, $headers['Content-Type']);
        $this->assertEquals('text/plain', $headers->content_type->type);
        $this->assertEquals('iso-8859-1', $headers->content_type->charset);
    }

    public function test_date(): void
    {
        $headers = new Headers();
        $this->assertInstanceOf(Headers\Date::class, $headers->date);

        $now = new DateTime();
        $headers->date = $now;
        $this->assertInstanceOf(Headers\Date::class, $headers->date);
        $this->assertEquals($now, $headers->date);
    }

    public function test_etag(): void
    {
        $headers = new Headers();
        $this->assertNull($headers->etag);

        $etag = uniqid();
        $headers->etag = $etag;
        $this->assertSame($etag, $headers->etag);

        $headers->etag = null;
        $this->assertNull($headers->etag);
    }

    public function test_last_modified()
    {
        $headers = new Headers();
        $this->assertInstanceOf(Headers\Date::class, $headers->last_modified);
        $this->assertEmpty((string) $headers->last_modified);

        $value = DateTime::now();
        $headers->last_modified = $value;
        $this->assertEquals($value, $headers->last_modified);

        $headers->last_modified = null;
        $this->assertEmpty((string) $headers->last_modified);
    }

    public function test_should_fail_on_blank_location(): void
    {
        $headers = new Headers();
        $this->expectException(InvalidArgumentException::class);
        $headers->location = '';
    }

    public function test_retry_after(): void
    {
        $headers = new Headers();
        $this->assertNull($headers->retry_after);

        $headers->retry_after = 123;
        $this->assertEquals(123, $headers->retry_after);

        $value = DateTime::now();
        $headers->retry_after = $value;
        $this->assertEquals($value, $headers->retry_after);
    }

    /**
     * @dataProvider provide_test_date_header
     */
    public function test_date_header(string $field, mixed $value, string $expected): void
    {
        $headers = new Headers();

        if ($field !== Headers::HEADER_RETRY_AFTER) {
            $this->assertInstanceOf(Headers\Date::class, $headers[$field]);
        }

        $headers[$field] = $value;

        $this->assertInstanceOf(Headers\Date::class, $headers[$field]);
        $this->assertEquals($expected, (string) $headers[$field]);
    }

    public function provide_test_date_header(): array
    {
        $value1 = new DateTime();
        $value2 = new \DateTime();
        $value3 = (string) $value1;
        $expected = $value1->utc->as_rfc1123;

        return [

            [ 'Date', $value1, $expected ],
            [ 'Date', $value2, $expected ],
            [ 'Date', $value3, $expected ],

            [ 'Expires', $value1, $expected ],
            [ 'Expires', $value2, $expected ],
            [ 'Expires', $value3, $expected ],

            [ 'If-Modified-Since', $value1, $expected ],
            [ 'If-Modified-Since', $value2, $expected ],
            [ 'If-Modified-Since', $value3, $expected ],
            [ 'If-Modified-Since', $value3 . ";length=xxxx", $expected ],

            [ 'If-Unmodified-Since', $value1, $expected ],
            [ 'If-Unmodified-Since', $value2, $expected ],
            [ 'If-Unmodified-Since', $value3, $expected ],

            [ 'Retry-After', $value1, $expected ],
            [ 'Retry-After', $value2, $expected ],
            [ 'Retry-After', $value3, $expected ],

        ];
    }

    /**
     * @dataProvider provide_getters
     *
     * @param class-string $expected
     */
    public function test_getters(string $getter, string $expected): void
    {
        $headers = new Headers();

        $this->assertInstanceOf($expected, $headers->$getter);
    }

    public function provide_getters(): array
    {
        return [

            [ 'cache_control', Headers\CacheControl::class ],
            [ 'content_disposition', Headers\ContentDisposition::class ],
            [ 'content_type', Headers\ContentType::class ],
            [ 'date', Headers\Date::class ],
            [ 'expires', Headers\Date::class ],
            [ 'if_modified_since', Headers\Date::class ],
            [ 'if_unmodified_since', Headers\Date::class ],

        ];
    }

    /**
     * @dataProvider provide_test_empty_date
     */
    public function test_empty_date(string $field)
    {
        $headers = new Headers();

        $this->assertInstanceOf(Headers\Date::class, $headers[$field]);
        $this->assertTrue($headers[$field]->is_empty);
    }

    public function provide_test_empty_date(): array
    {
        return [

            [ 'Date' ],
            [ 'Expires' ],
            [ 'If-Modified-Since' ],
            [ 'If-Unmodified-Since' ]

        ];
    }

    public function test_to_string_with_empty_dates(): void
    {
        $headers = new Headers([

            'Content-Type' => 'text/plain'

        ]);

        $this->assertTrue($headers['Date']->is_empty);
        $this->assertTrue($headers['Expires']->is_empty);
        $this->assertTrue($headers['If-Modified-Since']->is_empty);
        $this->assertTrue($headers['If-Unmodified-Since']->is_empty);

        $this->assertEquals("Content-Type: text/plain\r\n", (string) $headers);
    }

    public function test_clone(): void
    {
        $headers = new Headers();
        $headers_cache_control = $headers['Cache-Control'];
        $clone = clone $headers;
        $clone_cache_control = $clone['Cache-Control'];

        $this->assertNotSame($clone_cache_control, $headers_cache_control);
    }

    public function test_should_iterate(): void
    {
        $headers = new Headers([

            'REQUEST_URI' => '/',
            'HTTP_CACHE_CONTROL' => 'public',
            'HTTP_DATE' => 'now',
            'HTTP_EXPIRES' => '+1 month'

        ]);

        $names = [];

        foreach (array_keys(iterator_to_array($headers)) as $field) {
            $names[] = $field;
        }

        $this->assertEquals([ 'Cache-Control', 'Date', 'Expires' ], $names);
    }

    public function test_should_send_headers(): void
    {
        $now = new DateTime('now', 'utc');
        $in_one_month = new DateTime('+1 month', 'utc');

        $headers = $this
            ->getMockBuilder(Headers::class)
            ->onlyMethods([ 'send_header' ])
            ->getMock();

        $headers->expects($this->exactly(4))
            ->method('send_header')
            ->withConsecutive(
                [ "Cache-Control", "public" ],
                [ "X-Empty-3", "0" ],
                [ "Date", $now->as_rfc1123 ],
                [ "Expires", $in_one_month->as_rfc1123 ]
            );

        /* @var $headers Headers */

        $headers['Cache-Control'] = 'public';
        $headers['X-Empty-1'] = null;
        $headers['X-Empty-2'] = '';
        $headers['X-Empty-3'] = 0;
        $headers['Date'] = $now;
        $headers['Expires'] = $in_one_month;

        $headers();
    }
}
