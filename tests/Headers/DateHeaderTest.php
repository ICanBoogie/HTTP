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

use ICanBoogie\DateTime;
use ICanBoogie\HTTP\Headers\Date;
use ICanBoogie\HTTP\Headers\Date as DateHeader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateHeaderTest extends TestCase
{
    public function test_from_datetime(): void
    {
        $datetime = new \DateTime();
        $sut = DateHeader::from($datetime);
        $datetime->setTimezone(new \DateTimeZone('GMT'));

        $this->assertEquals($datetime->format('D, d M Y H:i:s') . ' GMT', (string) $sut);
    }

    public function test_from_string(): void
    {
        $datetime = new \DateTime('now', new \DateTimeZone('GMT'));

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) DateHeader::from($datetime->format('D, d M Y H:i:s P'))
        );

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) DateHeader::from($datetime->format('D, d M Y H:i:s'))
        );

        $this->assertEquals(
            $datetime->format('D, d M Y H:i:s') . ' GMT',
            (string) DateHeader::from($datetime->format('Y-m-d H:i:s'))
        );
    }

    #[DataProvider('provide_test_to_string')]
    public function test_to_string($expected, $datetime)
    {
        $field = Date::from($datetime);

        $this->assertEquals($expected, (string) $field);
    }

    public static function provide_test_to_string(): array
    {
        $now = DateTime::now();

        return [

            [ $now->as_rfc1123, $now ],
            [ '', DateTime::none() ],
            [ '', null ]

        ];
    }
}
