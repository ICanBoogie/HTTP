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
 * Options to create {@link File} instances.
 */
interface FileOptions
{
    /**
     * Name of the file.
     */
    public const OPTION_NAME = 'name';

    /**
     * MIME type of the file.
     */
    public const OPTION_TYPE = 'type';

    /**
     * Size of the file.
     */
    public const OPTION_SIZE = 'size';

    /**
     * Temporary filename.
     */
    public const OPTION_TMP_NAME = 'tmp_name';

    /**
     * Error code, one of `UPLOAD_ERR_*`.
     */
    public const OPTION_ERROR = 'error';

    /**
     * Pathname of the file.
     */
    public const OPTION_PATHNAME = 'pathname';
}
