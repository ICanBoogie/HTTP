<?php

namespace Test\ICanBoogie\HTTP;

use DateTimeInterface;
use ICanBoogie\DateTime;
use ICanBoogie\HTTP\FileResponse;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;
use ICanBoogie\HTTP\RequestOptions;
use ICanBoogie\HTTP\RequestRange;
use ICanBoogie\HTTP\ResponseStatus;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function filemtime;

final class FileResponseTest extends TestCase
{
    public function test_should_throw_exception_on_directory(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/Expected file, got directory\:/");

        new FileResponse(__DIR__, Request::from());
    }

    public function test_should_throw_exception_on_invalid_file(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches("/File does not exist\:/");

        new FileResponse(uniqid(), Request::from());
    }

    #[DataProvider('provide_test_closure_body')]
    public function test_closure_body(int $status, bool $expect_output): void
    {
        $file = create_file();
        $sut = new FileResponse($file, Request::from());
        $sut->status = $status;

        $actual = (string) $sut;

        if ($expect_output) {
            $this->assertStringEndsWith(file_get_contents($file), $actual);
        } else {
            $this->assertStringEndsNotWith(file_get_contents($file), $actual);
        }
    }

    public static function provide_test_closure_body(): array
    {
        return [

            [ ResponseStatus::STATUS_OK, true ],
            [ ResponseStatus::STATUS_NOT_MODIFIED, false ],
            [ ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE, false ],

        ];
    }

    #[DataProvider('provide_test_invoke')]
    public function test_invoke(string $cache_control, bool $is_modified, int $expected): void
    {
        $request = Request::from([ Request::OPTION_HEADERS => [ 'Cache-Control' => $cache_control ] ]);
        $file = create_file();
        $response = new class($file, $request, $is_modified) extends FileResponse
        {
            public int $send_headers_calls = 0;
            public int $send_body_calls = 0;

            public function __construct(
                SplFileInfo|string $file,
                Request $request,
                private readonly bool $is_modified_override,
            ) {
                parent::__construct($file, $request);
            }

            // The overload is required for the test; disregard IntelliJ
            public bool $is_modified {
                get => $this->is_modified_override;
            }

            protected function send_headers(Headers $headers): bool
            {
                $this->send_headers_calls++;

                return true;
            }

            protected function send_body(mixed $body): void
            {
                $this->send_body_calls++;
            }
        };

        $response();

        $this->assertEquals($expected, $response->status->code);
        $this->assertEquals(1, $response->send_headers_calls);
        // A 304 has no body.
        $this->assertEquals($expected === 304 ? 0 : 1, $response->send_body_calls);
    }

    #[DataProvider('provide_test_invoke_with_range')]
    public function test_invoke_with_range(
        string $cache_control,
        bool $is_modified,
        bool $is_satisfiable,
        bool $is_total,
        int $expected,
    ): void {
        $headers = new Headers();
        $headers['If-Range'] = $etag = "123";

        if ($is_satisfiable) {
            $headers['Range'] = $is_total ? "bytes=0-399" : "bytes=10-200";
        } else {
            $headers['Range'] = "bytes=500-";
        }

        $range = RequestRange::from($headers, 400, $etag);
        $file = create_file();
        $request = Request::from([ Request::OPTION_HEADERS => [ 'Cache-Control' => $cache_control ] ]);
        $response = new class($file, $request, $is_modified, $range) extends FileResponse
        {
            public int $send_headers_calls = 0;
            public int $send_body_calls = 0;

            public function __construct(
                SplFileInfo|string $file,
                Request $request,
                private bool $override_is_modified,
                private RequestRange $override_range,
            ) {
                parent::__construct($file, $request);
            }

            // The overload is required for the test; disregard IntelliJ
            public bool $is_modified {
                get => $this->override_is_modified;
            }

            // The overload is required for the test; disregard IntelliJ
            public ?RequestRange $range {
                get => $this->override_range;
            }

            protected function send_headers(Headers $headers): bool
            {
                $this->send_headers_calls++;

                return true;
            }

            protected function send_body(mixed $body): void
            {
                $this->send_body_calls++;
            }
        };

        $response();

        $this->assertEquals($expected, $response->status->code);
        $this->assertEquals(1, $response->send_headers_calls);
        // A 304 has no body.
        $this->assertEquals($expected === 304 ? 0 : 1, $response->send_body_calls);
    }

    public static function provide_test_invoke_with_range(): array
    {
        return [

            [ 'no-cache', false, false, true, ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE ],
            [ 'no-cache', false, true, false, ResponseStatus::STATUS_PARTIAL_CONTENT ],
            [ 'no-cache', false, true, true, ResponseStatus::STATUS_OK ],
            [ '', false, true, true, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ '', true, true, true, ResponseStatus::STATUS_OK ],

        ];
    }

    public static function provide_test_invoke(): array
    {
        return [

            [ '', false, ResponseStatus::STATUS_NOT_MODIFIED ],
            [ 'no-cache', false, ResponseStatus::STATUS_OK ],
            [ '', true, ResponseStatus::STATUS_OK ],
            [ 'no-cache', true, ResponseStatus::STATUS_OK ],

        ];
    }

    #[DataProvider('provide_test_get_content_type')]
    public function test_get_content_type(
        string $expected,
        string $file,
        array $options = [],
        array $headers = [],
    ): void {
        $response = new FileResponse($file, Request::from(), $options, $headers);
        $this->assertEquals($expected, (string)$response->headers->content_type);
    }

    public static function provide_test_get_content_type(): array
    {
        return [

            [ 'application/octet-stream', create_file() ],
            [ 'text/plain', create_file(), [ FileResponse::OPTION_MIME => 'text/plain' ] ],
            [ 'text/plain', create_file(), [], [ 'Content-Type' => 'text/plain' ] ],
            [ 'image/png', create_image('.png') ],
            [ 'text/plain', create_image('.png'), [ FileResponse::OPTION_MIME => 'text/plain' ] ],
            [ 'text/plain', create_image('.png'), [], [ 'Content-Type' => 'text/plain' ] ],

        ];
    }

    #[DataProvider('provide_test_get_etag')]
    public function test_get_etag(string $expected, string $file, array $options = [], array $headers = []): void
    {
        $response = new FileResponse($file, Request::from(), $options, $headers);
        $this->assertEquals($expected, $response->headers->etag);
    }

    public static function provide_test_get_etag(): array
    {
        $file = create_file();
        $file_etag = sprintf('"%x-%x"', filemtime($file), filesize($file));
        $file_hash_custom = '"' . FileResponse::hash_file($file) . '"';

        return [

            [ $file_etag, $file ],
            [ $file_hash_custom, $file, [ FileResponse::OPTION_ETAG => $file_hash_custom ] ],
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
        $response = new FileResponse($file, Request::from(), $options, $headers);
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
            [ $expires2, $file, [ FileResponse::OPTION_EXPIRES => $expires2_str ] ],
            [ $expires2, $file, [ FileResponse::OPTION_EXPIRES => $expires2 ] ],
            [ $expires2, $file, [], [ 'Expires' => $expires2_str ] ],
            [ $expires2, $file, [], [ 'Expires' => $expires2 ] ],

        ];
    }

    public function test_get_modified_time(): void
    {
        $file = create_file();
        $response = new FileResponse($file, Request::from());
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

        $response = new FileResponse($file, Request::from([ RequestOptions::OPTION_HEADERS => $request_headers ]), [
            FileResponse::OPTION_ETAG => $etag,
        ]);

        $this->assertSame($expected, $response->is_modified);
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
        $response = new FileResponse($file, Request::from(), [ FileResponse::OPTION_FILENAME => $filename ]);

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
        $response = new FileResponse(__FILE__, $request);
        $actual = (string) $response;

        $this->assertStringContainsString("Accept-Ranges: $type", $actual);
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

        $response = new FileResponse($pathname, $request, [ FileResponse::OPTION_ETAG => $etag ]);

        /* @var $response FileResponse */

        ob_start();

        $response();

        $content = ob_get_clean();

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
        $response = new FileResponse(create_file(), Request::from());
        $actual = (string) $response;

        $this->assertMatchesRegularExpression('/^Cache-Control: private, max-age=\d+\r$/m', $actual);
        $this->assertStringContainsString('Expires: ', $actual);
        $this->assertStringNotContainsString('public', $actual);
    }

    #[DataProvider('provide_test_cache_control_is_respected')]
    public function test_cache_control_is_respected(string $cache_control): void
    {
        $response = new FileResponse(create_file(), Request::from(), headers: [ 'Cache-Control' => $cache_control ]);
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

    public function test_cache_control_with_expires(): void
    {
        $response = new FileResponse(create_file(), Request::from(), [

            FileResponse::OPTION_EXPIRES => '+1 hour',

        ], [ 'Cache-Control' => 'public' ]);

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

        $response = new FileResponse($file, $request, [ FileResponse::OPTION_ETAG => $etag ]);

        ob_start();
        $response();
        $content = ob_get_clean();

        $this->assertEquals($expected, $response->status->code);
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
        $response = new FileResponse($file, $request);

        ob_start();
        $response();
        $content = ob_get_clean();

        $this->assertEquals(ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE, $response->status->code);
        $this->assertSame('', $content);

        $actual = (string) $response;

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

        $response = new FileResponse($file, $request);

        ob_start();
        $response();
        $content = ob_get_clean();

        $this->assertEquals(ResponseStatus::STATUS_PARTIAL_CONTENT, $response->status->code);
        $this->assertSame(substr($data, 0, 100), $content);
    }

    public function test_precondition_failed_headers(): void
    {
        $file = create_file();
        $request = Request::from([

            Request::OPTION_METHOD => RequestMethod::METHOD_PUT,
            Request::OPTION_HEADERS => [ 'If-None-Match' => '*' ],

        ]);

        $response = new FileResponse($file, $request);

        // The status is resolved when the response is sent.
        ob_start();
        $response();
        ob_end_clean();

        $string = (string) $response;

        $this->assertStringStartsWith("HTTP/1.1 412 ", $string);
        $this->assertStringContainsString("Content-Length: 0\r\n", $string);
    }

    public function test_non_matching_if_none_match_does_not_fail_precondition(): void
    {
        $request = Request::from([

            Request::OPTION_METHOD => RequestMethod::METHOD_PUT,
            Request::OPTION_HEADERS => [ 'If-None-Match' => '"other"' ],

        ]);

        $response = new FileResponse(create_file(), $request, [ FileResponse::OPTION_ETAG => '"abc"' ]);

        ob_start();
        $response();
        ob_end_clean();

        $this->assertSame(200, $response->status->code);
    }

    #[DataProvider('provide_test_option_etag')]
    public function test_option_etag_is_quoted(string $given, string $expected): void
    {
        $response = new FileResponse(create_file(), Request::from([]), [ FileResponse::OPTION_ETAG => $given ]);

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

        new FileResponse(create_file(), Request::from([]), [ FileResponse::OPTION_ETAG => 'a"b' ]);
    }
}
