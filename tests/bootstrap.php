<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

#
# Cleanup sandbox
#

$di = new \RegexIterator(new \DirectoryIterator(__DIR__ . '/sandbox'), '/^bytes/');

foreach ($di as $file)
{
	unlink($file->getPathname());
}
