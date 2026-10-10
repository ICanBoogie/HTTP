<?php

namespace Test\ICanBoogie\HTTP;

use Closure;
use DateTimeInterface;
use ICanBoogie\DateTime;
use ICanBoogie\HTTP\FileResponse;
use ICanBoogie\HTTP\FinalResponse;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;
use ICanBoogie\HTTP\RequestOptions;
use ICanBoogie\HTTP\ResponseStatus;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function filemtime;

final class FileResponseTest extends TestCase
{
    public function test_should_throw_exception_on_directory(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/Expected file, got directory\:/");

        new FileResponse(__DIR__);
    }

    public function test_should_throw_exception_on_invalid_file(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches("/File does not exist\:/");

        new FileResponse(uniqid());
    }

    /**
     * @param array<string, string> $request_headers
     */
    #[DataProvider('provide_test_closure_body')]
    public function test_closure_body(array $request_headers, bool $expect_output): void
    {
        $file = create_file();
        $size = filesize($file);
        $request_headers = str_replace('{size}', (string) $size, json_encode($request_headers));
        $request = Request::from([ Request::OPTION_HEADERS => json_decode($request_headers, true) ]);
        $sut = new FileResponse($file);

        $actual = $sut->__toString();

        // Serialization doesn't use the request.
        $this->assertStringEndsWith(file_get_contents($file), $actual);

        $final = $sut->finalize($request);

        $this->assertSame($expect_output, $final->body !== null);
    }

    // @phpstan-ignore-next-line
    public static function provide_test_closure_body(): array
    {
        return [

            'ok' => [ [], true ],
            'not modified' => [ [ 'If-None-Match' => '*' ], false ],
            'range not satisfiable' => [ [ 'Range' => 'bytes={size}-' ], false ],

        ];
    }

    #[DataProvider('provide_test_finalize')]
    public function test_finalize(string $cache_control, bool $is_modified, int $expected): void
    {
        $headers = [ 'Cache-Control' => $cache_control ];

        if (!$is_modified) {
            $headers['If-None-Match'] = '*';
        }

        $request = Request::from([ Request::OPTION_HEADERS => $headers ]);
        $response = new FileResponse(create_file());

        $final = $response->finalize($request);

        $this->assertSame($expected, $final->status->code);
        // A 304 has no body.
        $this->assertSame($expected !== 304, $final->body !== null);
        // Finalizing doesn't change the response.
        $this->assertSame(200, $response->status->code);
    }

    public static function provide_test_finalize(): array
    {
        return [

            [ '', false, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ 'no-cache', false, ResponseStatus::STATUS_OK ],
            [ '', true, ResponseStatus::STATUS_OK ],
            [ 'no-cache', true, ResponseStatus::STATUS_OK ],

        ];
    }

    #[DataProvider('provide_test_finalize_with_range')]
    public function test_finalize_with_range(
        string $cache_control,
        bool $is_modified,
        bool $is_satisfiable,
        bool $is_total,
        int $expected,
    ): void {
        $file = create_file();
        $size = filesize($file);
        $headers = [ 'Cache-Control' => $cache_control ];

        if ($is_satisfiable) {
            $headers['Range'] = $is_total ? "bytes=0-" . ($size - 1) : "bytes=10-200";
        } else {
            $headers['Range'] = "bytes=$size-";
        }

        if (!$is_modified) {
            $headers['If-None-Match'] = '*';
        }

        $request = Request::from([ Request::OPTION_HEADERS => $headers ]);

        $final = new FileResponse($file)->finalize($request);

        $this->assertSame($expected, $final->status->code);
        $this->assertSame($expected === 304 || $expected === 416, $final->body === null);
    }

    public static function provide_test_finalize_with_range(): array
    {
        return [

            [ 'no-cache', false, false, true, ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE ],
            [ 'no-cache', false, true, false, ResponseStatus::STATUS_PARTIAL_CONTENT ],
            [ 'no-cache', false, true, true, ResponseStatus::STATUS_OK ],
            [ '', false, true, true, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ '', true, true, true, ResponseStatus::STATUS_OK ],

        ];
    }

    #[DataProvider('provide_test_get_content_type')]
    public function test_get_content_type(
        string $expected,
        string $file,
        array $options = [],
        array $headers = [],
    ): void {
        $response = new FileResponse($file, ...$options, headers: $headers);
        $this->assertEquals($expected, (string)$response->headers->content_type);
    }

    public static function provide_test_get_content_type(): array
    {
        return [

            [ 'application/octet-stream', create_file() ],
            [ 'text/plain', create_file(), [ 'mime' => 'text/plain' ] ],
            [ 'text/plain', create_file(), [], [ 'Content-Type' => 'text/plain' ] ],
            [ 'image/png', create_image('.png') ],
            [ 'text/plain', create_image('.png'), [ 'mime' => 'text/plain' ] ],
            [ 'text/plain', create_image('.png'), [], [ 'Content-Type' => 'text/plain' ] ],

        ];
    }

    #[DataProvider('provide_test_get_etag')]
    public function test_get_etag(string $expected, string $file, array $options = [], array $headers = []): void
    {
        $response = new FileResponse($file, ...$options, headers: $headers);
        $this->assertEquals($expected, $response->headers->etag);
    }

    public static function provide_test_get_etag(): array
    {
        $file = create_file();
        $file_etag = sprintf('"%x-%x"', filemtime($file), filesize($file));
        $file_hash_custom = '"' . FileResponse::hash_file($file) . '"';

        return [

            [ $file_etag, $file ],
            [ $file_hash_custom, $file, [ 'etag' => $file_hash_custom ] ],
            [ $file_hash_custom, $file, [], [ 'ETag' => $file_hash_custom ] ],

        ];
    }

    #[DataProvider('provide_test_get_expires')]
    public function test_get_expires(
        DateTimeInterface $expected,
        string $file,
        array $options = [],
        array $headers = [],
    ): void {
        $response = new FileResponse($file, ...$options, headers: $headers);
        $actual = $response->expires->delegate;

        $this->assertGreaterThanOrEqual($expected, $actual);
    }

    public static function provide_test_get_expires(): array
    {
        $file = create_file();
        $expires_default = DateTime::from(FileResponse::DEFAULT_EXPIRES);
        $expires2_str = "+10 hour";
        $expires2 = DateTime::from($expires2_str);

        return [

            [ $expires_default, $file ],
            [ $expires2, $file, [ 'expires' => $expires2_str ] ],
            [ $expires2, $file, [ 'expires' => $expires2 ] ],
            [ $expires2, $file, [], [ 'Expires' => $expires2_str ] ],
            [ $expires2, $file, [], [ 'Expires' => $expires2 ] ],

        ];
    }

    public function test_get_modified_time(): void
    {
        $file = create_file();
        $response = new FileResponse($file);
        $this->assertEquals(filemtime($file), $response->modified_time);
    }

    #[DataProvider('provide_test_get_is_modified')]
    public function test_get_is_modified(
        bool $expected,
        array $request_headers,
        false|int $modified_time = false,
        ?string $etag = null,
    ): void {
        $file = create_file();
        if ($modified_time) {
            touch($file, $modified_time);
        }

        $response = new FileResponse($file, etag: $etag);
        $request = Request::from([ RequestOptions::OPTION_HEADERS => $request_headers ]);

        $this->assertSame($expected, $response->is_modified_for($request));
    }

    public static function provide_test_get_is_modified(): array
    {
        $modified_since = DateTime::from('-2 month');
        $modified_time_older = DateTime::from('-6 month')->timestamp;
        $modified_time_newer = DateTime::from('-1 month')->timestamp;
        $etag = uniqid();

        return [

            [ true, [] ],
            [ true, [ 'If-Modified-Since' => (string)$modified_since ] ],
            [ false, [ 'If-Modified-Since' => (string)$modified_since ], $modified_time_older ],
            [ true, [ 'If-Modified-Since' => (string)$modified_since ], $modified_time_newer ],
            [
                true,
                [ 'If-Modified-Since' => (string)$modified_since, 'If-None-Match' => uniqid() ],
                $modified_time_older,
            ],
            [
                true,
                [ 'If-Modified-Since' => (string)$modified_since, 'If-None-Match' => uniqid() ],
                $modified_time_older,
            ],
            // If-None-Match takes precedence over If-Modified-Since
            [
                false,
                [ 'If-Modified-Since' => (string)$modified_since, 'If-None-Match' => $etag ],
                $modified_time_newer,
                $etag,
            ],
            [ false, [ 'If-None-Match' => $etag ], false, $etag ],
            [ true, [ 'If-None-Match' => uniqid() ], false, $etag ],
            [ false, [ 'If-None-Match' => '*' ], false, $etag ],
            [ false, [ 'If-None-Match' => '"abc"' ], false, '"abc"' ],
            [ false, [ 'If-None-Match' => 'W/"abc"' ], false, '"abc"' ],
            [ false, [ 'If-None-Match' => '"abc"' ], false, 'W/"abc"' ],
            [ false, [ 'If-None-Match' => '"xyz", "abc"' ], false, '"abc"' ],
            [ false, [ 'If-None-Match' => '"x,y",W/"abc"' ], false, '"abc"' ],
            [ true, [ 'If-None-Match' => '"xyz", "abcd"' ], false, '"abc"' ],
            [
                false,
                [ 'If-Modified-Since' => (string)$modified_since, 'If-None-Match' => $etag ],
                $modified_time_older,
                $etag,
            ],

        ];
    }

    #[DataProvider('provide_test_filename')]
    public function test_filename(string $file, string|bool $filename, string $expected): void
    {
        $response = new FileResponse($file, filename: $filename);

        $this->assertEquals('binary', (string)$response->headers['Content-Transfer-Encoding']);
        $this->assertEquals('File Transfer', (string)$response->headers['Content-Description']);
        $this->assertEquals('attachment', $response->headers->content_disposition->type);
        $this->assertEquals($expected, $response->headers->content_disposition->filename);
    }

    public static function provide_test_filename(): array
    {
        $file = create_file();
        $filename = "Filename" . uniqid() . ".png";

        return [

            [ $file, true, basename($file) ],
            [ $file, $filename, $filename ],

        ];
    }

    #[DataProvider('provide_test_accept_ranges')]
    public function test_accept_ranges(RequestMethod $method, string $type): void
    {
        $request = Request::from([ Request::OPTION_URI => '/', 'method' => $method ]);
        $response = new FileResponse(__FILE__);
        [ $final ] = self::resolve($response, $request);

        $this->assertStringContainsString("Accept-Ranges: $type", (string) $final->headers);
    }

    public static function provide_test_accept_ranges(): array
    {
        return [

            [ RequestMethod::METHOD_GET, 'bytes' ],
            [ RequestMethod::METHOD_HEAD, 'bytes' ],
            [ RequestMethod::METHOD_POST, 'none' ],
            [ RequestMethod::METHOD_PUT, 'none' ],

        ];
    }

    #[DataProvider('provide_test_range_response')]
    public function test_range_response(string $bytes, string $pathname, string $expected): void
    {
        $etag = '"' . sha1_file($pathname) . '"';

        $request = Request::from([

            Request::OPTION_HEADERS => [

                'Range' => "bytes=$bytes",
                'If-Range' => $etag,

            ],

        ]);

        $response = new FileResponse($pathname, etag: $etag);

        [ , $content ] = self::resolve($response, $request);

        $this->assertSame($expected, $content);
    }

    public static function provide_test_range_response(): array
    {
        $pathname = create_file();
        $data = file_get_contents($pathname);

        return [

            [ '0-499', $pathname, substr($data, 0, 500) ],
            [ '500-999', $pathname, substr($data, 500, 500) ],
            [ '-500', $pathname, substr($data, -500) ],
            [ '-500', $pathname, substr($data, -500) ],
            [ '9500-', $pathname, substr($data, -500) ],
            [ 'bytes=0-9999', $pathname, $data ],
            [ '0-0', $pathname, $data[0] ],
            [ '9000-99999', $pathname, substr($data, 9000) ],
            [ '-99999', $pathname, $data ],

        ];
    }

    public function test_cache_control_defaults_to_private(): void
    {
        $response = new FileResponse(create_file());
        $actual = (string) $response;

        $this->assertMatchesRegularExpression('/^Cache-Control: private, max-age=\d+\r$/m', $actual);
        $this->assertStringContainsString('Expires: ', $actual);
        $this->assertStringNotContainsString('public', $actual);
    }

    #[DataProvider('provide_test_cache_control_is_respected')]
    public function test_cache_control_is_respected(string $cache_control): void
    {
        $response = new FileResponse(create_file(), headers: [ 'Cache-Control' => $cache_control ]);
        $actual = (string) $response;

        $this->assertStringContainsString("Cache-Control: $cache_control\r\n", $actual);
        $this->assertStringNotContainsString('Expires: ', $actual);
    }

    public static function provide_test_cache_control_is_respected(): array
    {
        return [

            [ 'no-store' ],
            [ 'private, max-age=60' ],
            [ 'public, max-age=31536000' ],

        ];
    }

    public function test_etag_parameter_conflicts_with_etag_header(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileResponse(create_file(), etag: '"a"', headers: [ 'ETag' => '"b"' ]);
    }

    public function test_cache_control_with_expires(): void
    {
        $response = new FileResponse(
            create_file(),
            expires: '+1 hour',
            headers: [ 'Cache-Control' => 'public' ],
        );

        $actual = (string) $response;

        $this->assertStringContainsString("Cache-Control: public\r\n", $actual);
        $this->assertStringContainsString('Expires: ', $actual);
    }

    #[DataProvider('provide_test_not_modified_only_for_get_and_head')]
    public function test_not_modified_only_for_get_and_head(RequestMethod $method, int $expected): void
    {
        $file = create_file();
        $etag = '"abc"';
        $request = Request::from([

            Request::OPTION_METHOD => $method,
            Request::OPTION_HEADERS => [ 'If-None-Match' => $etag ],

        ]);

        $response = new FileResponse($file, etag: $etag);

        [ $final, $content ] = self::resolve($response, $request);

        $this->assertEquals($expected, $final->status->code);
        $this->assertSame('', $content);
    }

    public static function provide_test_not_modified_only_for_get_and_head(): array
    {
        return [

            [ RequestMethod::METHOD_GET, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ RequestMethod::METHOD_HEAD, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ RequestMethod::METHOD_POST, ResponseStatus::STATUS_PRECONDITION_FAILED ],
            [ RequestMethod::METHOD_PUT, ResponseStatus::STATUS_PRECONDITION_FAILED ],

        ];
    }

    public function test_range_not_satisfiable(): void
    {
        $file = create_file();
        $size = filesize($file);
        $request = Request::from([ Request::OPTION_HEADERS => [ 'Range' => "bytes=$size-" ] ]);
        $response = new FileResponse($file);

        [ $final, $content ] = self::resolve($response, $request);

        $this->assertEquals(ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE, $final->status->code);
        $this->assertSame('', $content);

        $actual = (string) $final->headers;

        $this->assertStringContainsString("Content-Range: bytes */$size\r\n", $actual);
        $this->assertStringContainsString("Content-Length: 0\r\n", $actual);
    }

    public function test_range_with_if_range_date(): void
    {
        $file = create_file();
        $data = file_get_contents($file);
        $last_modified = (string) \ICanBoogie\HTTP\Headers\Date::from(filemtime($file));
        $request = Request::from([ Request::OPTION_HEADERS => [

            'Range' => 'bytes=0-99',
            'If-Range' => $last_modified,

        ] ]);

        $response = new FileResponse($file);

        [ $final, $content ] = self::resolve($response, $request);

        $this->assertEquals(ResponseStatus::STATUS_PARTIAL_CONTENT, $final->status->code);
        $this->assertSame(substr($data, 0, 100), $content);
    }

    public function test_precondition_failed_headers(): void
    {
        $file = create_file();
        $request = Request::from([

            Request::OPTION_METHOD => RequestMethod::METHOD_PUT,
            Request::OPTION_HEADERS => [ 'If-None-Match' => '*' ],

        ]);

        $response = new FileResponse($file);

        [ $final ] = self::resolve($response, $request);

        $this->assertSame(412, $final->status->code);
        $this->assertStringContainsString("Content-Length: 0\r\n", (string) $final->headers);
    }

    public function test_non_matching_if_none_match_does_not_fail_precondition(): void
    {
        $request = Request::from([

            Request::OPTION_METHOD => RequestMethod::METHOD_PUT,
            Request::OPTION_HEADERS => [ 'If-None-Match' => '"other"' ],

        ]);

        $response = new FileResponse(create_file(), etag: '"abc"');

        [ $final ] = self::resolve($response, $request);

        $this->assertSame(200, $final->status->code);
    }

    #[DataProvider('provide_test_option_etag')]
    public function test_option_etag_is_quoted(string $given, string $expected): void
    {
        $response = new FileResponse(create_file(), etag: $given);

        $this->assertSame($expected, $response->headers->etag);
    }

    public static function provide_test_option_etag(): array
    {
        return [

            'unquoted' => [ 'abc', '"abc"' ],
            'quoted' => [ '"abc"', '"abc"' ],
            'weak' => [ 'W/"abc"', 'W/"abc"' ],
            'base64' => [ 'a+b/c==', '"a+b/c=="' ],

        ];
    }

    public function test_option_etag_with_a_quote_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileResponse(create_file(), etag: 'a"b');
    }

    /**
     * Finalizes the response for a request, and captures the body.
     *
     * @return array{ 0: FinalResponse, 1: string }
     */
    private static function resolve(FileResponse $response, Request $request): array
    {
        $final = $response->finalize($request);
        $body = '';

        if ($final->body instanceof Closure) {
            ob_start();

            try {
                ($final->body)($final);
            } finally {
                $body = ob_get_clean();
            }
        }

        return [ $final, $body ];
    }
}
