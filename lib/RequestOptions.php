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
 * The options that may be used to create a request.
 */
interface RequestOptions
{
    public const OPTION_PATH_PARAMS = 'path_params';
    public const OPTION_QUERY_PARAMS = 'query_params';
    public const OPTION_REQUEST_PARAMS = 'request_params';
    public const OPTION_COOKIE = 'cookie';
    public const OPTION_FILES = 'files';
    public const OPTION_HEADERS = 'headers';
    public const OPTION_CACHE_CONTROL = 'cache_control';
    public const OPTION_CONTENT_LENGTH = 'content_length';
    public const OPTION_IP = 'ip';
    public const OPTION_IS_LOCAL = 'is_local';
    public const OPTION_IS_XHR = 'is_xhr';
    public const OPTION_METHOD = 'method';
    public const OPTION_PATH = 'path';
    public const OPTION_REFERER = 'referer';
    public const OPTION_URI = 'uri';
    public const OPTION_USER_AGENT = 'user_agent';
}
