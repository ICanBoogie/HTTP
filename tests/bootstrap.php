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

use function random_bytes;

require __DIR__ . '/../vendor/autoload.php';

/*
 * The expected value for file size
 */
const CREATE_FILE_SIZE = 10000;

#
# Cleanup sandbox
#

$iterator = new \RegexIterator(new \DirectoryIterator(__DIR__ . '/sandbox'), '/^bytes/');

foreach ($iterator as $file) {
    unlink($file->getPathname());
}

function generate_pathname()
{
    return __DIR__ . '/sandbox/bytes-' . uniqid();
}

/**
 * Creates a file with random bytes.
 *
 * @param string $extension
 *
 * @return string The pathname of the file.
 */
function create_file(string $extension = ''): string
{
    $pathname = generate_pathname() . $extension;
    file_put_contents($pathname, random_bytes(CREATE_FILE_SIZE));

    return $pathname;
}

function create_image(string $extension = '.png'): string
{
    $image = imagecreatetruecolor(200, 200);
    $pathname = generate_pathname() . $extension;

    imagepng($image, $pathname);

    return $pathname;
}
