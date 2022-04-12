<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use InvalidArgumentException;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    public function test_constructor()
    {
        $status = new Status(Status::MOVED_PERMANENTLY, "Over the rainbow");

        $this->assertEquals(Status::MOVED_PERMANENTLY, $status->code);
        $this->assertEquals("Over the rainbow", $status->message);
        $this->assertEquals("301 Over the rainbow", (string) $status);

        $status->message = null;
        $this->assertEquals("Moved Permanently", $status->message);
        $this->assertEquals("301 Moved Permanently", (string) $status);
    }

    /**
     * @dataProvider provide_test_from
     *
     * @param mixed $source
     * @param string $expected
     */
    public function test_from($source, $expected)
    {
        $status = Status::from($source);
        $this->assertEquals($expected, (string) $status);
    }

    public function provide_test_from()
    {
        return [

            [ Status::OK , "200 OK" ],
            [ [ Status::OK, "Madonna" ], "200 Madonna" ],
            [ "200 Madonna", "200 Madonna" ]

        ];
    }

    public function test_from_invalid_status()
    {
        $this->expectException(InvalidArgumentException::class);

        Status::from("Invalid status");
    }

    public function test_from_invalid_status_code()
    {
        $this->expectException(StatusCodeNotValid::class);

        Status::from("987 Invalid");
    }

    public function test_constructor_with_invalid_code()
    {
        $this->expectException(StatusCodeNotValid::class);

        new Status(987);
    }

    public function test_set_code_invalid()
    {
        $status = new Status();

        $this->expectException(StatusCodeNotValid::class);

        $status->code = 12345;
    }

    public function test_set_code()
    {
        $status = new Status();
        $status->code = Status::NOT_FOUND;
        $this->assertEquals(Status::NOT_FOUND, $status->code);
    }

    public function test_is_valid()
    {
        $status = new Status(Status::OK);
        $this->assertTrue($status->is_valid);
    }

    public function test_is_informational()
    {
        $status = new Status(Status::OK);
        $this->assertFalse($status->is_informational);
        $status->code = 100;
        $this->assertTrue($status->is_informational);
        $status->code = 199;
        $this->assertTrue($status->is_informational);
    }

    public function test_is_successful()
    {
        $status = new Status();
        $status->code = 199;
        $this->assertFalse($status->is_successful);
        $status->code = 200;
        $this->assertTrue($status->is_successful);
        $status->code = 299;
        $this->assertTrue($status->is_successful);
        $status->code = 300;
        $this->assertFalse($status->is_successful);
    }

    public function test_is_redirect()
    {
        $status = new Status();
        $status->code = 299;
        $this->assertFalse($status->is_redirect);
        $status->code = 300;
        $this->assertTrue($status->is_redirect);
        $status->code = 399;
        $this->assertTrue($status->is_redirect);
        $status->code = 400;
        $this->assertFalse($status->is_redirect);
    }

    public function test_is_client_error()
    {
        $status = new Status();
        $status->code = 399;
        $this->assertFalse($status->is_client_error);
        $status->code = 400;
        $this->assertTrue($status->is_client_error);
        $status->code = 499;
        $this->assertTrue($status->is_client_error);
        $status->code = Status::INTERNAL_SERVER_ERROR;
        $this->assertFalse($status->is_client_error);
    }

    public function test_is_server_error()
    {
        $status = new Status();
        $status->code = 499;
        $this->assertFalse($status->is_server_error);
        $status->code = Status::INTERNAL_SERVER_ERROR;
        $this->assertTrue($status->is_server_error);
        $status->code = 599;
        $this->assertTrue($status->is_server_error);
    }

    public function test_is_ok()
    {
        $status = new Status();
        $this->assertTrue($status->is_ok);
        $status->code = Status::NOT_FOUND;
        $this->assertFalse($status->is_ok);
    }

    public function test_is_forbidden()
    {
        $status = new Status();
        $this->assertFalse($status->is_forbidden);
        $status->code = Status::FORBIDDEN;
        $this->assertTrue($status->is_forbidden);
    }

    public function test_is_not_found()
    {
        $status = new Status();
        $this->assertFalse($status->is_not_found);
        $status->code = Status::NOT_FOUND;
        $this->assertTrue($status->is_not_found);
    }

    public function test_is_empty()
    {
        $status = new Status();
        $this->assertFalse($status->is_empty);
        $status->code = Status::CREATED;
        $this->assertTrue($status->is_empty);
        $status->code = Status::NO_CONTENT;
        $this->assertTrue($status->is_empty);
        $status->code = Status::NOT_MODIFIED;
        $this->assertTrue($status->is_empty);
    }

    /**
     * @dataProvider provide_test_is_cacheable
     *
     * @param int $code
     * @param bool $expected
     */
    public function test_is_cacheable($code, $expected)
    {
        $status = new Status($code);
        $this->assertEquals($expected, $status->is_cacheable);
    }

    public function provide_test_is_cacheable()
    {
        return [

            [ Status::OK, true ],
            [ Status::CREATED, false ],
            [ Status::ACCEPTED, false ],
            [ Status::NON_AUTHORITATIVE_INFORMATION, true ],
            [ Status::MULTIPLE_CHOICES, true ],
            [ Status::MOVED_PERMANENTLY, true ],
            [ Status::FOUND, true ],
            [ Status::SEE_OTHER, false ],
            [ Status::NOT_FOUND, true ],
            [ Status::METHOD_NOT_ALLOWED, false ],
            [ Status::GONE, true ]

        ];
    }
}
