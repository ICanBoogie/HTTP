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

/**
 * File information.
 */
class FileInfo
{
	const TYPES = [

		'.doc'  => 'application/msword',
		'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'.gif'  => 'image/gif',
		'.jpg'  => 'image/jpeg',
		'.jpeg' => 'image/jpeg',
		'.js'   => 'application/javascript',
		'.json' => 'application/json',
		'.mp3'  => 'audio/mpeg',
		'.odt'  => 'application/vnd.oasis.opendocument.text',
		'.pdf'  => 'application/pdf',
		'.php'  => 'application/x-php',
		'.png'  => 'image/png',
		'.psd'  => 'application/psd',
		'.rar'  => 'application/rar',
		'.txt'  => 'text/plain',
		'.zip'  => 'application/zip',
		'.xls'  => 'application/vnd.ms-excel',
		'.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

	];

	const FORCED_TYPES = [

		'.js',
		'.json',
		'.php',
		'.txt'

	];

	const TYPES_ALIAS = [

		'text/x-php' => 'application/x-php'

	];

	/**
	 * Resolves the MIME type of a file.
	 *
	 * @param string $pathname Pathname to the file.
	 * @param string $extension The variable passed by reference receives the extension
	 * of the file.
	 *
	 * @return string The MIME type of the file, or `application/octet-stream` if it could not
	 * be determined.
	 */
	static public function resolve_type($pathname, &$extension = null)
	{
		$extension = '.' . strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
		$types = self::TYPES;

		if (in_array($extension, self::FORCED_TYPES))
		{
			return $types[$extension];
		}

		if (file_exists($pathname) && extension_loaded('fileinfo'))
		{
			$fi = new \finfo(FILEINFO_MIME_TYPE);
			$type = $fi->file($pathname);

			if ($type)
			{
				$alias = self::TYPES_ALIAS;
				return isset($alias[$type]) ? $alias[$type] : $type;
			}
		} // @codeCoverageIgnore

		if (isset($types[$extension]))
		{
			return $types[$extension];
		}

		return 'application/octet-stream'; // @codeCoverageIgnore
	}
}
