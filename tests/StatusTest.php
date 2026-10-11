<?php

namespace Test\ICanBoogie\HTTP;

use ICanBoogie\HTTP\Status;
use ICanBoogie\HTTP\StatusCodeNotValid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;

use function array_keys;
use function range;
use function strtoupper;

final class StatusTest extends TestCase
{
    public function test_from_code(): void
    {
        $status = Status::from(Status::MOVED_PERMANENTLY);

        $this->assertSame(301, $status->code);
        $this->assertSame("Moved Permanently", $status->reason);
        $this->assertSame("301 Moved Permanently", (string)$status);
    }

    public function test_from_status_returns_the_status(): void
    {
        $status = Status::from(Status::FOUND, "Over the rainbow");

        $this->assertSame($status, Status::from($status));
    }

    public function test_from_status_with_reason(): void
    {
        $status = Status::from(Status::from(Status::FOUND), "Over the rainbow");

        $this->assertSame(302, $status->code);
        $this->assertSame("Over the rainbow", $status->reason);
    }

    public function test_custom_reason(): void
    {
        $status = Status::from(Status::OK, "Madonna");

        $this->assertSame("200 Madonna", (string)$status);
        $this->assertNotEquals(Status::of_ok(), $status);
    }

    public function test_standard_reason_is_equal_to_the_default(): void
    {
        $this->assertEquals(Status::of_ok(), Status::from(Status::OK, "OK"));
    }

    public function test_empty_reason_is_kept(): void
    {
        $status = Status::from(Status::OK, "");

        $this->assertSame("", $status->reason);
        $this->assertSame("200 ", (string)$status);
    }

    public function test_non_standard_code(): void
    {
        $status = Status::from(499);

        $this->assertSame("", $status->reason);
        $this->assertTrue($status->is_client_error);
        $this->assertTrue($status->is_error);

        $status = Status::from(499, "Client Closed Request");

        $this->assertSame("499 Client Closed Request", (string)$status);
    }

    public function test_equality(): void
    {
        $this->assertEquals(Status::from(404), Status::of_not_found());
        $this->assertNotEquals(Status::from(404), Status::of_forbidden());
    }

    #[DataProvider('provide_invalid_codes')]
    public function test_invalid_code(int $code): void
    {
        $this->expectException(StatusCodeNotValid::class);

        Status::from($code);
    }

    public static function provide_invalid_codes(): array
    {
        return [

            [ -1 ],
            [ 0 ],
            [ 99 ],
            [ 600 ],
            [ 987 ],

        ];
    }

    public function test_invalid_code_with_status(): void
    {
        $this->expectException(StatusCodeNotValid::class);

        Status::from(600, "Reason");
    }

    public function test_constructor_is_private(): void
    {
        $this->assertTrue((new ReflectionClass(Status::class))->getConstructor()?->isPrivate());
    }

    public function test_properties_are_readonly(): void
    {
        $status = Status::of_ok();

        $this->expectException(\Error::class);

        $status->code = Status::NOT_FOUND;
    }

    public function test_reason_is_readonly(): void
    {
        $status = Status::of_ok();

        $this->expectException(\Error::class);

        $status->reason = "Madonna";
    }

    public function test_every_constant_has_a_reason(): void
    {
        $constants = (new ReflectionClass(Status::class))
            ->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC);

        $this->assertNotEmpty($constants);

        foreach ($constants as $constant) {
            $status = Status::from($constant->getValue());

            $this->assertNotSame("", $status->reason, $constant->getName());
        }
    }

    #[DataProvider('provide_helpers')]
    public function test_helpers(string $helper, int $expected): void
    {
        $status = Status::$helper();

        $this->assertSame($expected, $status->code);
        $this->assertSame(Status::from($expected), $status);
    }

    public static function provide_helpers(): array
    {
        $helpers = [
            'ok' => Status::OK,
            'created' => Status::CREATED,
            'accepted' => Status::ACCEPTED,
            'no_content' => Status::NO_CONTENT,
            'moved_permanently' => Status::MOVED_PERMANENTLY,
            'found' => Status::FOUND,
            'see_other' => Status::SEE_OTHER,
            'not_modified' => Status::NOT_MODIFIED,
            'temporary_redirect' => Status::TEMPORARY_REDIRECT,
            'permanent_redirect' => Status::PERMANENT_REDIRECT,
            'bad_request' => Status::BAD_REQUEST,
            'unauthorized' => Status::UNAUTHORIZED,
            'forbidden' => Status::FORBIDDEN,
            'not_found' => Status::NOT_FOUND,
            'method_not_allowed' => Status::METHOD_NOT_ALLOWED,
            'conflict' => Status::CONFLICT,
            'unprocessable_entity' => Status::UNPROCESSABLE_ENTITY,
            'too_many_requests' => Status::TOO_MANY_REQUESTS,
            'internal_server_error' => Status::INTERNAL_SERVER_ERROR,
            'bad_gateway' => Status::BAD_GATEWAY,
            'service_unavailable' => Status::SERVICE_UNAVAILABLE,
            'gateway_timeout' => Status::GATEWAY_TIMEOUT,
        ];

        $cases = [];

        foreach ($helpers as $name => $code) {
            $cases[$name] = [ "of_$name", $code ];
        }

        return $cases;
    }

    public function test_helpers_are_named_after_constants(): void
    {
        foreach (array_keys(self::provide_helpers()) as $name) {
            $this->assertTrue(
                (new ReflectionClass(Status::class))->hasConstant(strtoupper($name)),
                $name
            );
        }
    }

    /**
     * @param array<string, bool> $expected
     */
    #[DataProvider('provide_classes')]
    public function test_classes(int $code, array $expected): void
    {
        $status = Status::from($code);

        $this->assertSame($expected['informational'], $status->is_informational);
        $this->assertSame($expected['successful'], $status->is_successful);
        $this->assertSame($expected['redirect'], $status->is_redirect);
        $this->assertSame($expected['client_error'], $status->is_client_error);
        $this->assertSame($expected['server_error'], $status->is_server_error);
        $this->assertSame($expected['client_error'] || $expected['server_error'], $status->is_error);
    }

    public static function provide_classes(): array
    {
        $none = [
            'informational' => false,
            'successful' => false,
            'redirect' => false,
            'client_error' => false,
            'server_error' => false,
        ];

        return [

            [ 100, [ 'informational' => true ] + $none ],
            [ 199, [ 'informational' => true ] + $none ],
            [ 200, [ 'successful' => true ] + $none ],
            [ 299, [ 'successful' => true ] + $none ],
            [ 300, [ 'redirect' => true ] + $none ],
            [ 399, [ 'redirect' => true ] + $none ],
            [ 400, [ 'client_error' => true ] + $none ],
            [ 499, [ 'client_error' => true ] + $none ],
            [ 500, [ 'server_error' => true ] + $none ],
            [ 599, [ 'server_error' => true ] + $none ],

        ];
    }

    public function test_is_ok(): void
    {
        $this->assertTrue(Status::of_ok()->is_ok);
        $this->assertFalse(Status::of_created()->is_ok);
    }

    public function test_is_forbidden(): void
    {
        $this->assertTrue(Status::of_forbidden()->is_forbidden);
        $this->assertFalse(Status::of_unauthorized()->is_forbidden);
    }

    public function test_is_not_found(): void
    {
        $this->assertTrue(Status::of_not_found()->is_not_found);
        $this->assertFalse(Status::of_forbidden()->is_not_found);
    }

    #[DataProvider('provide_has_no_body')]
    public function test_has_no_body(int $code, bool $expected): void
    {
        $this->assertSame($expected, Status::from($code)->has_no_body);
    }

    public static function provide_has_no_body(): array
    {
        $cases = [];

        foreach (range(100, 103) as $code) {
            $cases["$code"] = [ $code, true ];
        }

        return $cases + [

            '200' => [ Status::OK, false ],
            '201' => [ Status::CREATED, false ],
            '204' => [ Status::NO_CONTENT, true ],
            '206' => [ Status::PARTIAL_CONTENT, false ],
            '304' => [ Status::NOT_MODIFIED, true ],
            '404' => [ Status::NOT_FOUND, false ],
            '500' => [ Status::INTERNAL_SERVER_ERROR, false ],

        ];
    }

    #[DataProvider('provide_is_heuristically_cacheable')]
    public function test_is_heuristically_cacheable(int $code, bool $expected): void
    {
        $this->assertSame($expected, Status::from($code)->is_heuristically_cacheable);
    }

    public static function provide_is_heuristically_cacheable(): array
    {
        $cacheable = [ 200, 203, 204, 206, 300, 301, 308, 404, 405, 410, 414, 501 ];
        $not_cacheable = [ 201, 202, 302, 303, 304, 307, 400, 401, 403, 500, 503 ];

        $cases = [];

        foreach ($cacheable as $code) {
            $cases["$code"] = [ $code, true ];
        }

        foreach ($not_cacheable as $code) {
            $cases["$code"] = [ $code, false ];
        }

        return $cases;
    }
}
