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

use ICanBoogie\HTTP\ResponseStatus;
use ICanBoogie\HTTP\Status;
use ICanBoogie\HTTP\StatusCodeNotValid;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
    public function test_constructor()
    {
        $status = new Status(ResponseStatus::STATUS_MOVED_PERMANENTLY, "Over the rainbow");

        $this->assertEquals(ResponseStatus::STATUS_MOVED_PERMANENTLY, $status->code);
        $this->assertEquals("Over the rainbow", $status->message);
        $this->assertEquals("301 Over the rainbow", (string) $status);

        $status->message = null;
        $this->assertEquals("Moved Permanently", $status->message);
        $this->assertEquals("301 Moved Permanently", (string) $status);
    }

    /**
     * @dataProvider provide_test_from
     */
    public function test_from(mixed $source, string $expected): void
    {
        $status = Status::from($source);
        $this->assertEquals($expected, (string) $status);
    }

    public function provide_test_from(): array
    {
        return [

            [ ResponseStatus::STATUS_OK, "200 OK" ],
            [ [ ResponseStatus::STATUS_OK, "Madonna" ], "200 Madonna" ],
            [ "200 Madonna", "200 Madonna" ]

        ];
    }

    public function test_from_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Status::from("Invalid status");
    }

    public function test_from_invalid_status_code(): void
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
        $status->code = ResponseStatus::STATUS_NOT_FOUND;
        $this->assertEquals(ResponseStatus::STATUS_NOT_FOUND, $status->code);
    }

    public function test_is_valid()
    {
        $status = new Status(ResponseStatus::STATUS_OK);
        $this->assertTrue($status->is_valid);
    }

    public function test_is_informational(): void
    {
        $status = new Status(ResponseStatus::STATUS_OK);
        $this->assertFalse($status->is_informational);
        $status->code = 100;
        $this->assertTrue($status->is_informational);
        $status->code = 199;
        $this->assertTrue($status->is_informational);
    }

    public function test_is_successful(): void
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

    public function test_is_redirect(): void
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

    public function test_is_client_error(): void
    {
        $status = new Status();
        $status->code = 399;
        $this->assertFalse($status->is_client_error);
        $status->code = 400;
        $this->assertTrue($status->is_client_error);
        $status->code = 499;
        $this->assertTrue($status->is_client_error);
        $status->code = ResponseStatus::STATUS_INTERNAL_SERVER_ERROR;
        $this->assertFalse($status->is_client_error);
    }

    public function test_is_server_error(): void
    {
        $status = new Status();
        $status->code = 499;
        $this->assertFalse($status->is_server_error);
        $status->code = ResponseStatus::STATUS_INTERNAL_SERVER_ERROR;
        $this->assertTrue($status->is_server_error);
        $status->code = 599;
        $this->assertTrue($status->is_server_error);
    }

    public function test_is_ok(): void
    {
        $status = new Status();
        $this->assertTrue($status->is_ok);
        $status->code = ResponseStatus::STATUS_NOT_FOUND;
        $this->assertFalse($status->is_ok);
    }

    public function test_is_forbidden(): void
    {
        $status = new Status();
        $this->assertFalse($status->is_forbidden);
        $status->code = ResponseStatus::STATUS_FORBIDDEN;
        $this->assertTrue($status->is_forbidden);
    }

    public function test_is_not_found(): void
    {
        $status = new Status();
        $this->assertFalse($status->is_not_found);
        $status->code = ResponseStatus::STATUS_NOT_FOUND;
        $this->assertTrue($status->is_not_found);
    }

    public function test_is_empty(): void
    {
        $status = new Status();
        $this->assertFalse($status->is_empty);
        $status->code = ResponseStatus::STATUS_CREATED;
        $this->assertTrue($status->is_empty);
        $status->code = ResponseStatus::STATUS_NO_CONTENT;
        $this->assertTrue($status->is_empty);
        $status->code = ResponseStatus::STATUS_NOT_MODIFIED;
        $this->assertTrue($status->is_empty);
    }

    /**
     * @dataProvider provide_test_is_cacheable
     */
    public function test_is_cacheable(int $code, bool $expected): void
    {
        $status = new Status($code);
        $this->assertEquals($expected, $status->is_cacheable);
    }

    public function provide_test_is_cacheable(): array
    {
        return [

            [ ResponseStatus::STATUS_OK, true ],
            [ ResponseStatus::STATUS_CREATED, false ],
            [ ResponseStatus::STATUS_ACCEPTED, false ],
            [ ResponseStatus::STATUS_NON_AUTHORITATIVE_INFORMATION, true ],
            [ ResponseStatus::STATUS_MULTIPLE_CHOICES, true ],
            [ ResponseStatus::STATUS_MOVED_PERMANENTLY, true ],
            [ ResponseStatus::STATUS_NOT_FOUND, true ],
            [ ResponseStatus::STATUS_SEE_OTHER, false ],
            [ ResponseStatus::STATUS_NOT_FOUND, true ],
            [ ResponseStatus::STATUS_METHOD_NOT_ALLOWED, false ],
            [ ResponseStatus::STATUS_GONE, true ]

        ];
    }
}
