<?php

namespace ICanBoogie\HTTP;

/**
 * Options to create {@see File} instances.
 */
interface FileOptions
{
    /**
     * Name of the file.
     */
    public const string OPTION_NAME = 'name';

    /**
     * MIME type of the file.
     */
    public const string OPTION_TYPE = 'type';

    /**
     * Size of the file.
     */
    public const string OPTION_SIZE = 'size';

    /**
     * Temporary filename.
     */
    public const string OPTION_TMP_NAME = 'tmp_name';

    /**
     * Error code, one of `UPLOAD_ERR_*`.
     */
    public const string OPTION_ERROR = 'error';

    /**
     * Pathname of the file.
     */
    public const string OPTION_PATHNAME = 'pathname';
}
