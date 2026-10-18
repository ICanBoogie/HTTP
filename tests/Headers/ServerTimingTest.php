<?php

namespace Test\ICanBoogie\HTTP\Headers;

use ICanBoogie\HTTP\Headers\ServerTiming;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServerTimingTest extends TestCase
{
    public function test_from_null_is_empty(): void
    {
        $actual = ServerTiming::from(null);

        $this->assertSame([], $actual->metrics);
        $this->assertSame('', (string) $actual);
    }

    public function test_from_instance_returns_it(): void
    {
        $timing = new ServerTiming();

        $actual = ServerTiming::from($timing);

        $this->assertSame($timing, $actual);
    }

    public function test_add(): void
    {
        $timing = new ServerTiming();
        $timing->add('db', 53.0);
        $timing->add('cache', 23.2, 'Cache Read');
        $timing->add('miss');

        $actual = (string) $timing;

        $this->assertSame('db;dur=53, cache;dur=23.2;desc="Cache Read", miss', $actual);
    }

    public function test_duration_is_rounded_to_two_decimals(): void
    {
        $timing = new ServerTiming();
        $timing->add('app', 334.424019);
        $timing->add('config', 47.7197);
        $timing->add('fast', 0.004);

        $actual = (string) $timing;

        $this->assertSame('app;dur=334.42, config;dur=47.72, fast;dur=0', $actual);
    }

    #[DataProvider('provide_from')]
    public function test_from(string $source, string $expected): void
    {
        $timing = ServerTiming::from($source);

        $actual = (string) $timing;

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{ string, string }>
     */
    public static function provide_from(): array
    {
        return [
            'single' => [ 'app;dur=47.2', 'app;dur=47.2' ],
            'many' => [ 'cache;desc="Cache Read";dur=23.2, db;dur=53, app;dur=47.2', 'cache;dur=23.2;desc="Cache Read", db;dur=53, app;dur=47.2' ],
            'comma in description' => [ 'a;desc="x, y";dur=1', 'a;dur=1;desc="x, y"' ],
            'escaped quote' => [ 'a;desc="say \"hi\""', 'a;desc="say \"hi\""' ],
            'unknown parameter ignored' => [ 'a;foo=bar;dur=1', 'a;dur=1' ],
            'whitespace' => [ ' a ; dur=1 ,  b ', 'a;dur=1, b' ],
            'empty members' => [ 'a,,b,', 'a, b' ],
            'spec 1' => [ 'miss, db;dur=53, app;dur=47.2', 'miss, db;dur=53, app;dur=47.2' ],
            'spec 2' => [ 'customView, dc;desc=atl', 'customView, dc;desc="atl"' ],
            'spec 3' => [ 'cache;desc="Cache Read";dur=23.2', 'cache;dur=23.2;desc="Cache Read"' ],
            'spec 4' => [ 'total;dur=123.4', 'total;dur=123.4' ],
            'duplicate metric names' => [ 'a;dur=1, a;dur=2', 'a;dur=1, a;dur=2' ],
            'non numeric duration' => [ 'a;dur=fast', 'a' ],
            'parameter without value' => [ 'a;dur;desc=x', 'a;desc="x"' ],
            'first parameter wins' => [ 'a;dur=1;dur=2', 'a;dur=1' ],
            'first parameter wins even if invalid' => [ 'a;dur=fast;dur=2', 'a' ],
            'extraneous characters after quoted value' => [ 'a;desc="x" junk;dur=1', 'a;dur=1;desc="x"' ],
            'metric without name' => [ ';dur=1, b', 'b' ],
        ];
    }

    public function test_parsed_metrics(): void
    {
        $timing = ServerTiming::from('cache;desc="Cache Read";dur=23.2');

        $this->assertCount(1, $timing->metrics);
        $this->assertSame('cache', $timing->metrics[0]->name);
        $this->assertSame(23.2, $timing->metrics[0]->duration);
        $this->assertSame('Cache Read', $timing->metrics[0]->description);
    }

    #[DataProvider('provide_invalid')]
    public function test_invalid(string $source): void
    {
        $this->expectException(InvalidArgumentException::class);

        ServerTiming::from($source);
    }

    /**
     * @return array<string, array{ string }>
     */
    public static function provide_invalid(): array
    {
        return [
            'invalid name' => [ 'a b;dur=1' ],
            'negative duration' => [ 'a;dur=-1' ],
            'header injection' => [ "a;desc=\"x\r\nSet-Cookie: x=1\"" ],
        ];
    }

    public function test_description_is_escaped(): void
    {
        $timing = new ServerTiming();
        $timing->add('a', null, 'say "hi"');

        $actual = (string) $timing;

        $this->assertSame('a;desc="say \"hi\""', $actual);
    }

    #[DataProvider('provide_empty_name')]
    public function test_empty_name_is_rejected(string $name): void
    {
        $timing = new ServerTiming();

        $this->expectException(InvalidArgumentException::class);

        $timing->add($name);
    }

    /**
     * @return array<string, array{ string }>
     */
    public static function provide_empty_name(): array
    {
        return [
            'empty' => [ '' ],
        ];
    }

    public function test_negative_duration_is_rejected(): void
    {
        $timing = new ServerTiming();

        $this->expectException(InvalidArgumentException::class);

        $timing->add('a', -1.0);
    }

    public function test_unsafe_description_is_rejected(): void
    {
        $timing = new ServerTiming();

        $this->expectException(InvalidArgumentException::class);

        $timing->add('a', null, "x\r\nSet-Cookie: x=1");
    }
}
