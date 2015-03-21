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

require __DIR__ . '/../vendor/autoload.php';

#
# Cleanup sandbox
#

$di = new \RegexIterator(new \DirectoryIterator(__DIR__ . '/sandbox'), '/^bytes/');

foreach ($di as $file)
{
	unlink($file->getPathname());
}

/**
 * Creates a file with random bytes.
 *
 * @param string $extension
 *
 * @return string The pathname of the file.
 */
function create_file($extension = '')
{
	$pathname = __DIR__ . '/sandbox/bytes-' . uniqid() . $extension;
	file_put_contents($pathname, openssl_random_pseudo_bytes(2048));

	return $pathname;
}
