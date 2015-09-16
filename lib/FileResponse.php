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

/**
 * Representation of an HTTP response delivering a file.
 *
 * @property-read \SplFileInfo $file
 * @property-read int $modified_time
 * @property-read bool $is_modified
 */
class FileResponse extends Response
{
	/**
	 * Specifies the `ETag` header field of the response. If it is not defined the SHA-1
	 * of the file is used instead.
	 */
	const OPTION_ETAG = 'etag';

	/**
	 * Specifies the expiration date as a {@link DateTime} instance or a relative date
	 * such as "+3 month", which maps to the `Expires` header field. The `max-age` directive of
	 * the `Cache-Control` header field is computed from the current time. If it is not
	 * defined {@link DEFAULT_EXPIRES} is used instead.
	 */
	const OPTION_EXPIRES = 'expires';

	/**
	 * Specifies the filename of the file and forces download. The following header are updated:
	 * `Content-Transfer-Encoding`, `Content-Description`, and `Content-Dispositon`.
	 */
	const OPTION_FILENAME = 'filename';

	/**
	 * Specifies the MIME of the file, which maps to the `Content-Type` header field. If it is
	 * not defined the MIME is guessed using `finfo::file()`.
	 */
	const OPTION_MIME = 'mime';

	const DEFAULT_EXPIRES = '+1 month';
	const DEFAULT_MIME = 'application/octet-stream';

	/**
	 * @var \SplFileInfo
	 */
	protected $file;

	/**
	 * @return \SplFileInfo
	 */
	protected function get_file()
	{
		return $this->file;
	}

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @param string|\SplFileInfo $file
	 * @param Request $request
	 * @param array $options
	 * @param array $headers
	 */
	public function __construct($file, Request $request, array $options = [], $headers = [])
	{
		$this->file = $file instanceof \SplFileInfo ? $file : new \SplFileInfo($file);
		$this->request = $request;
		$this->apply_options($options, $headers);

		parent::__construct(function() {

			$this->send_file($this->file);

		}, Status::OK, $headers);
	}

	/**
	 * Changes the status to {@link Status::NOT_MODIFIED} if the request's Cache-Control has
	 * 'no-cache' and {@link is_modified} is false.
	 */
	public function __invoke()
	{
		if ($this->request->cache_control->cacheable != 'no-cache' && !$this->is_modified)
		{
			$this->status = Status::NOT_MODIFIED;
		}

		parent::__invoke();
	}

	/**
	 * The following headers are always modified:
	 *
	 * - `Cache-Control`: sets _cacheable_ to _public_.
	 * - `Expires`: is set to "+1 month".
	 *
	 * If the status code is {@link Stauts::NOT_MODIFIED} the following headers are unset:
	 *
	 * - `Content-Type`
	 * - `Content-Length`
	 *
	 * Otherwise, the following header is set:
	 *
	 * - `Content-Type`:
	 *
	 * @inheritdoc
	 */
	protected function finalize(Headers &$headers, &$body)
	{
		parent::finalize($headers, $body);

		$expires = $this->expires;
		$headers['Expires'] = $expires;
		$headers['Cache-Control']->cacheable = 'public';
		$headers['Cache-Control']->max_age = $expires->timestamp - DateTime::now()->timestamp;
		$headers['Content-Type'] = $this->content_type;

		if ($this->status->code === Status::NOT_MODIFIED)
		{
			unset($headers['Content-Length']);

			return;
		}

		$headers['Content-Length'] = $this->file->getSize();
		$headers['Last-Modified'] = $this->modified_time;
	}

	/**
	 * Sends the file.
	 *
	 * @param \SplFileInfo $file
	 *
	 * @codeCoverageIgnore
	 */
	protected function send_file(\SplFileInfo $file)
	{
		$out = fopen('php://output', 'wb');
		$fh = fopen($file->getPathname(), 'rb');

		stream_copy_to_stream($fh, $out);

		fclose($out);
		fclose($fh);
	}

	/**
	 * If the content type returned by the parent is empty the method tries to obtain it from
	 * the file, if it fails {@link DEFAULT_MIME} is used as fallback.
	 *
	 * @inheritdoc
	 */
	protected function get_content_type()
	{
		$content_type = parent::get_content_type();

		if ($content_type->value)
		{
			return $content_type;
		}

		$mime = null;

		if (function_exists('finfo_file'))
		{
			$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->file);
		}

		return new Headers\ContentType($mime ?: self::DEFAULT_MIME);
	}

	/**
	 * If the etag returned by the parent is empty the method returns a SHA-1 of the file.
	 *
	 * @return string
	 */
	protected function get_etag()
	{
		return parent::get_etag() ?: sha1_file($this->file->getPathname());
	}

	/**
	 * If the date returned by the parent is empty the method returns a date created from
	 * {@link DEFAULT_EXPIRES}.
	 *
	 * @return DateTime|Headers\Date
	 */
	protected function get_expires()
	{
		$expires = parent::get_expires();

		if (!$expires->is_empty)
		{
			return $expires;
		}

		return DateTime::from(self::DEFAULT_EXPIRES);
	}

	/**
	 * Returns the timestamp at which the file was last modified.
	 *
	 * @return int
	 */
	protected function get_modified_time()
	{
		return $this->file->getMTime();
	}

	/**
	 * Whether the file as been modified since the last response.
	 *
	 * The file is considered modified if one of the following conditions is met:
	 *
	 * - The `If-Modified-Since` request header is empty.
	 * - The `If-Modified-Since` value is inferior to {@link $modified_time}.
	 * - The `If-None-Match` value doesn't match {@link $etag}.
	 *
	 * @return bool
	 */
	protected function get_is_modified()
	{
		/* @var $if_modified_since \ICanBoogie\DateTime */

		$headers = $this->request->headers;
		$if_none_match = $headers['If-None-Match'];
		$if_modified_since = $headers['If-Modified-Since'];

		return $if_modified_since->is_empty
		|| $if_modified_since->timestamp < $this->modified_time
		|| $if_none_match != $this->etag;
	}

	/**
	 * @param array $options
	 * @param Headers|array $headers
	 */
	protected function apply_options(array $options, &$headers)
	{
		foreach (array_filter($options) as $option => $value)
		{
			switch ($option)
			{
				case self::OPTION_ETAG:
					$headers['ETag'] = $value;
					break;

				case self::OPTION_EXPIRES:
					$headers['Expires'] = $value;
					break;

				case self::OPTION_FILENAME:
					$headers['Content-Transfer-Encoding'] = 'binary';
					$headers['Content-Description'] = 'File Transfer';
					$headers['Content-Disposition'] = new Headers\ContentDisposition('attachment', [

						'filename' => $value === true ? $this->file->getFilename() : $value

					]);
					break;

				case self::OPTION_MIME:
					$headers['Content-Type'] = $value;
					break;
			}
		}
	}
}
