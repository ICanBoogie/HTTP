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
	const OPTION_PATH_PARAMS = 'path_params';
	const OPTION_QUERY_PARAMS = 'query_params';
	const OPTION_REQUEST_PARAMS = 'request_params';
	const OPTION_COOKIE = 'cookie';
	const OPTION_FILES = 'files';
	const OPTION_HEADERS = 'headers';
	const OPTION_CACHE_CONTROL = 'cache_control';
	const OPTION_CONTENT_LENGTH = 'content_length';
	const OPTION_IP = 'ip';
	const OPTION_IS_LOCAL = 'is_local';
	const OPTION_IS_DELETE = 'is_delete';
	const OPTION_IS_CONNECT = 'is_connect';
	const OPTION_IS_GET = 'is_get';
	const OPTION_IS_HEAD = 'is_head';
	const OPTION_IS_OPTIONS = 'is_options';
	const OPTION_IS_PATCH = 'is_patch';
	const OPTION_IS_POST = 'is_post';
	const OPTION_IS_PUT = 'is_put';
	const OPTION_IS_TRACE = 'is_trace';
	const OPTION_IS_XHR = 'is_xhr';
	const OPTION_METHOD = 'method';
	const OPTION_PATH = 'path';
	const OPTION_REFERER = 'referer';
	const OPTION_URI = 'uri';
	const OPTION_USER_AGENT = 'user_agent';
}
