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

class FileResponseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_invoke
	 *
	 * @param string $cache_control
	 * @param bool $is_modified
	 * @param int $expected
	 */
	public function test_invoke($cache_control, $is_modified, $expected)
	{
		$request = Request::from([ 'headers' => [ 'Cache-Control' => $cache_control ] ]);

		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), $request ])
			->setMethods([ 'get_is_modified', 'send_headers', 'send_body' ])
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

		/* @var $response FileResponse */

		$response();

		$this->assertEquals($expected, $response->status->code);
	}

	public function provide_test_invoke()
	{
		return [

			[ '', false, Status::NOT_MODIFIED ],
			[ 'no-cache', false, Status::OK ],
			[ '', true, Status::OK ],
			[ 'no-cache', true, Status::OK ]

		];
	}

	public function test_send_body()
	{
		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), Request::from() ])
			->setMethods([ 'send_headers', 'send_file' ])
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
	 *
	 * @param string $expected
	 * @param string $file
	 * @param array $options
	 * @param array $headers
	 */
	public function test_get_content_type($expected, $file, $options = [], $headers = [])
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected, (string) $response->content_type);
	}

	public function provide_test_get_content_type()
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
	public function test_get_etag($expected, $file, $options = [], $headers = [])
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected, (string) $response->etag);
	}

	public function provide_test_get_etag()
	{
		$f1 = create_file();
		$f1_hash = sha1_file($f1);
		$f1_hash_custom = $f1_hash . '#' . uniqid();

		return [

			[ $f1_hash, $f1 ],
			[ $f1_hash_custom, $f1, [ FileResponse::OPTION_ETAG => $f1_hash_custom ] ],
			[ $f1_hash_custom, $f1, [ ], [ 'ETag' => $f1_hash_custom ] ],

		];
	}

	/**
	 * @dataProvider provide_test_get_expires
	 */
	public function test_get_expires(DateTime $expected, $file, $options = [], $headers = [])
	{
		$response = new FileResponse($file, Request::from(), $options, $headers);
		$this->assertEquals($expected->utc->format('YmdHi'), $response->expires->utc->format('YmdHi'));
	}

	public function provide_test_get_expires()
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

	public function test_get_modified_time()
	{
		$file = create_file();
		$response = new FileResponse($file, Request::from());
		$this->assertEquals(filemtime($file), $response->modified_time);
	}

	/**
	 * @dataProvider provide_test_get_is_modified
	 *
	 * @param bool $expected
	 * @param array $request_headers
	 * @param int|null $modified_time
	 * @param string|null $etag
	 */
	public function test_get_is_modified($expected, $request_headers, $modified_time = null, $etag = null)
	{
		$request = Request::from([ 'headers' => $request_headers ]);
		$response = $this
			->getMockBuilder(FileResponse::class)
			->setConstructorArgs([ create_file(), $request ])
			->setMethods([ 'get_modified_time', 'get_etag' ])
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
}
