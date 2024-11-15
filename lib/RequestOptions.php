<?php

namespace ICanBoogie\HTTP;

/**
 * The options that may be used to create a request.
 */
interface RequestOptions
{
    public const string OPTION_PATH_PARAMS = 'path_params';
    public const string OPTION_QUERY_PARAMS = 'query_params';
    public const string OPTION_REQUEST_PARAMS = 'request_params';
    public const string OPTION_COOKIE = 'cookie';
    public const string OPTION_FILES = 'files';
    public const string OPTION_HEADERS = 'headers';
    public const string OPTION_CACHE_CONTROL = 'cache_control';
    public const string OPTION_CONTENT_LENGTH = 'content_length';
    public const string OPTION_IP = 'ip';
    public const string OPTION_IS_LOCAL = 'is_local';
    public const string OPTION_IS_XHR = 'is_xhr';
    public const string OPTION_METHOD = 'method';
    public const string OPTION_PATH = 'path';
    public const string OPTION_REFERER = 'referer';
    public const string OPTION_URI = 'uri';
    public const string OPTION_USER_AGENT = 'user_agent';
}
