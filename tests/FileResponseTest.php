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

use ICanBoogie\DateTime;
use LogicException;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

final class FileResponseTest extends TestCase
{
	public function test_should_throw_exception_on_directory()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessageMatches("/Expected file, got directory\:/");

		new FileResponse(__DIR__, Request::from());
	}

	public function test_should_throw_exception_on_invalid_file()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessageMatches("/File does not exist\:/");

		new FileResponse(uniqid(), Request::from());
	}

	/**
     * @dataProvider provide_test_closure_body
     */
	public function test_closure_body(int $status, InvokedCount $expected)
	{
		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ __FILE__, Request::from()])
			->onlyMethods([ 'send_file', 'send_headers' ])
			->getMock();
		$response
			->expects($expected)
			->method('send_file');

		$response->status = $status;
		$response();
	}

	public function provide_test_closure_body(): array
	{
		return [

			[ Status::OK, $this->once() ],
			[ Status::NOT_MODIFIED, $this->never() ],
			[ Status::REQUESTED_RANGE_NOT_SATISFIABLE, $this->never() ]

		];
	}

	public function test_get_file()
	{
		$response = new FileResponse(__FILE__, Request::from());

		$this->assertInstanceOf(\SplFileInfo::class, $response->file);
		$this->assertEquals(__FILE__, $response->file->getPathname());
	}

	/**
	 * @dataProvider provide_test_invoke
	 */
	public function test_invoke(string $cache_control, bool $is_modified, int $expected): void
	{
		$request = Request::from([ Request::OPTION_HEADERS => [ 'Cache-Control' => $cache_control ] ]);

		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), $request ])
			->onlyMethods([ 'get_is_modified', 'send_headers', 'send_body' ])
			->getMockForAbstractClass();
		$response
			->expects($this->any())
			->method('get_is_modified')
			->willReturn($is_modified);
		$response
			->expects($this->once())
			->method('send_headers');
		$response
			->expects($this->once())
			->method('send_body');

		$response();

		$this->assertEquals($expected, $response->status->code);
	}

	/**
	 * @dataProvider provide_test_invoke_with_range
	 */
	public function test_invoke_with_range(string $cache_control, bool $is_modified, bool $is_satisfiable, bool $is_total, int $expected)
	{
		$range = $this
			->getMockBuilder(RequestRange::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'get_is_satisfiable', 'get_is_total' ])
			->getMock();
		$range
			->expects($this->any())
			->method('get_is_satisfiable')
			->willReturn($is_satisfiable);
		$range
			->expects($this->any())
			->method('get_is_total')
			->willReturn($is_total);

		$request = Request::from([ Request::OPTION_HEADERS => [ 'Cache-Control' => $cache_control ] ]);

		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), $request ])
			->onlyMethods([ 'get_is_modified', 'get_range', 'send_headers', 'send_body' ])
			->getMock();
		$response
			->expects($this->any())
			->method('get_is_modified')
			->willReturn($is_modified);
		$response
			->expects($this->any())
			->method('get_range')
			->willReturn($range);

		$response();

		$this->assertEquals($expected, $response->status->code);
	}

	public function provide_test_invoke_with_range(): array
	{
		return [

			[ 'no-cache', false, false, true, Status::REQUESTED_RANGE_NOT_SATISFIABLE ],
			[ 'no-cache', false, true, false, Status::PARTIAL_CONTENT ],
			[ 'no-cache', false, true, true, Status::OK ],
			[ '', false, true, true, Status::NOT_MODIFIED ],
			[ '', true, true, true, Status::OK ]

		];
	}

	public function provide_test_invoke(): array
	{
		return [

			[ '', false, Status::NOT_MODIFIED ],
			[ 'no-cache', false, Status::OK ],
			[ '', true, Status::OK ],
			[ 'no-cache', true, Status::OK ]

		];
	}

	public function test_send_body(): void
	{
		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), Request::from() ])
			->onlyMethods([ 'send_headers', 'send_file' ])
			->getMockForAbstractClass();
		$response
			->expects($this->once())
			->method('send_headers');
		$response
			->expects($this->once())
			->method('send_file');

		/* @var $response FileResponse */

		$response();
	}

	/**
	 * @dataProvider provide_test_get_content_type
	 */
	public function test_get_content_type(string $expected, string $file, array $options = [], array $headers = [])
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected, (string) $response->content_type);
	}

	public function provide_test_get_content_type(): array
	{
		return [

			[ 'application/octet-stream', create_file() ],
			[ 'text/plain', create_file(), [ FileResponse::OPTION_MIME => 'text/plain'] ],
			[ 'text/plain', create_file(), [], [ 'Content-Type' => 'text/plain'] ],
			[ 'image/png', create_image('.png') ],
			[ 'text/plain', create_image('.png'), [ FileResponse::OPTION_MIME => 'text/plain'] ],
			[ 'text/plain', create_image('.png'), [], [ 'Content-Type' => 'text/plain'] ],

		];
	}

	/**
	 * @dataProvider provide_test_get_etag
	 */
	public function test_get_etag(string $expected, string $file, array $options = [], array $headers = []): void
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected, (string) $response->etag);
	}

	public function provide_test_get_etag(): array
	{
		$file = create_file();
		$file_hash = FileResponse::hash_file($file);
		$file_hash_custom = $file_hash . '#' . uniqid();

		return [

			[ $file_hash, $file ],
			[ $file_hash_custom, $file, [ FileResponse::OPTION_ETAG => $file_hash_custom ] ],
			[ $file_hash_custom, $file, [ ], [ 'ETag' => $file_hash_custom ] ],

		];
	}

	/**
	 * @dataProvider provide_test_get_expires
	 */
	public function test_get_expires(DateTime $expected, string $file, array $options = [], array $headers = []): void
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected->utc->format('YmdHi'), $response->expires->utc->format('YmdHi'));
	}

	public function provide_test_get_expires(): array
	{
		$file = create_file();
		$expires_default = DateTime::from(FileResponse::DEFAULT_EXPIRES);
		$expires2_str = "+10 hour";
		$expires2 = DateTime::from($expires2_str);

		return [

			[ $expires_default, $file ],
			[ $expires2, $file, [ FileResponse::OPTION_EXPIRES => $expires2_str ] ],
			[ $expires2, $file, [ FileResponse::OPTION_EXPIRES => $expires2 ] ],
			[ $expires2, $file, [ ], [ 'Expires' => $expires2_str ] ],
			[ $expires2, $file, [ ], [ 'Expires' => $expires2 ] ],

		];
	}

	public function test_get_modified_time(): void
	{
		$file = create_file();
		$response = new FileResponse($file, Request::from());
		$this->assertEquals(filemtime($file), $response->modified_time);
	}

	/**
	 * @dataProvider provide_test_get_is_modified
	 */
	public function test_get_is_modified(bool $expected, array $request_headers, int $modified_time = null, string $etag = null)
	{
		$request = Request::from([ Request::OPTION_HEADERS => $request_headers ]);
		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), $request ])
			->onlyMethods([ 'get_modified_time', 'get_etag' ])
			->getMockForAbstractClass();
		$response
			->expects($this->any())
			->method('get_modified_time')
			->willReturn($modified_time);
		$response
			->expects($this->any())
			->method('get_etag')
			->willReturn($etag ?: uniqid());

		/* @var $response FileResponse */

		$this->assertSame($expected, $response->is_modified);
	}

	public function provide_test_get_is_modified()
	{
		$modified_since = DateTime::from('-2 month');
		$modified_time_older = DateTime::from('-6 month')->timestamp;
		$modified_time_newer = DateTime::from('-1 month')->timestamp;
		$etag = uniqid();

		return [

			[ true, [ ] ],
			[ true, [ 'If-Modified-Since' => (string) $modified_since ] ],
			[ true, [ 'If-Modified-Since' => (string) $modified_since ], $modified_time_older ],
			[ true, [ 'If-Modified-Since' => (string) $modified_since, 'If-None-Match' => uniqid() ], $modified_time_older ],
			[ true, [ 'If-Modified-Since' => (string) $modified_since, 'If-None-Match' => uniqid() ], $modified_time_older ],
			[ true, [ 'If-Modified-Since' => (string) $modified_since, 'If-None-Match' => $etag ], $modified_time_newer, $etag ],
			[ false, [ 'If-Modified-Since' => (string) $modified_since, 'If-None-Match' => $etag ], $modified_time_older, $etag ],

		];
	}

	/**
	 * @dataProvider provide_test_filename
	 *
	 * @param $file
	 * @param $filename
	 * @param $expected
	 */
	public function test_filename($file, $filename, $expected)
	{
		$response = new FileResponse($file, Request::from(), [ FileResponse::OPTION_FILENAME => $filename ]);

		$this->assertEquals('binary', (string) $response->headers['Content-Transfer-Encoding']);
		$this->assertEquals('File Transfer', (string) $response->headers['Content-Description']);
		$this->assertEquals('attachment', $response->headers['Content-Disposition']->type);
		$this->assertEquals($expected, $response->headers['Content-Disposition']->filename);
	}

	public function provide_test_filename()
	{
		$file = create_file();
		$filename = "Filename" . uniqid() . ".png";

		return [

			[ $file, true, basename($file) ],
			[ $file, $filename, $filename ]

		];
	}

	/**
	 * @dataProvider provide_test_accept_ranges
	 *
	 * @param string $method
	 * @param string $type
	 */
	public function test_accept_ranges($method, $type)
	{
		$request = Request::from([ Request::OPTION_URI => '/', 'method' => $method ]);

		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ __FILE__, $request ])
			->onlyMethods([ 'send_body' ])
			->getMock();

		/* @var $response FileResponse */

		$this->assertStringContainsString("Accept-Ranges: $type", (string) $response);
	}

	public function provide_test_accept_ranges()
	{
		return [

			[ Request::METHOD_GET, 'bytes' ],
			[ Request::METHOD_HEAD, 'bytes' ],
			[ Request::METHOD_POST, 'none' ],
			[ Request::METHOD_PUT, 'none' ]

		];
	}

	/**
	 * @dataProvider provide_test_range_response
	 *
	 * @param string $bytes
	 * @param string $pathname
	 * @param string $expected
	 */
	public function test_range_response($bytes, $pathname, $expected)
	{
		$etag = sha1_file($pathname);

		$request = Request::from([

			Request::OPTION_HEADERS => [

				'Range' => "bytes=$bytes",
				'If-Range' => $etag

			]

		]);

		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ $pathname, $request, [ FileResponse::OPTION_ETAG => $etag ] ])
			->onlyMethods([ 'send_headers' ])
			->getMock();

		/* @var $response FileResponse */

		ob_start();

		$response();

		$content = ob_get_clean();

		$this->assertSame($expected, $content);
	}

	public function provide_test_range_response()
	{
		$pathname = create_file();
		$data = file_get_contents($pathname);

		return [

			[ '0-499', $pathname, substr($data, 0, 500) ],
			[ '500-999', $pathname, substr($data, 500, 500) ],
			[ '-500', $pathname, substr($data, -500) ],
			[ '-500', $pathname, substr($data, -500) ],
			[ '9500-', $pathname, substr($data, -500) ],
			[ 'bytes=0-9999', $pathname, $data ]

		];
	}
}
