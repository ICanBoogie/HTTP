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
 * Maps options to the environment.
 */
class RequestOptionsMapper implements RequestOptions
{
    /**
     * Maps options to environment.
     *
     * The options mapped to the environment are removed from the `$options` array.
     *
     * @param array $options Reference to the options.
     * @param array $env Reference to the environment.
     *
     * @throws \InvalidArgumentException on invalid option.
     */
    public function map(array &$options, array &$env): void
    {
        $mappers = $this->get_mappers();

        foreach ($options as $option => &$value) {
            if (empty($mappers[$option])) {
                throw new \InvalidArgumentException("Option not supported: `$option`.");
            }

            $value = $mappers[$option]($value, $env);

            if ($value === null) {
                unset($options[$option]);
            }
        }
    }

    /**
     * Returns request properties mappers.
     *
     * @return \Closure[]
     */
    protected function get_mappers(): array
    {
        return [

            self::OPTION_PATH_PARAMS =>    function ($value) {
                return $value;
            },
            self::OPTION_QUERY_PARAMS =>   function ($value) {
                return $value;
            },
            self::OPTION_REQUEST_PARAMS => function ($value) {
                return $value;
            },
            self::OPTION_COOKIE =>         function ($value) {
                return $value;
            },
            self::OPTION_FILES =>          function ($value) {
                return $value;
            },
            self::OPTION_HEADERS =>        function ($value) {
                return ($value instanceof Headers) ? $value : new Headers($value);
            },

            self::OPTION_CACHE_CONTROL =>  function ($value, array &$env) {
                $env['HTTP_CACHE_CONTROL'] = $value;
            },
            self::OPTION_CONTENT_LENGTH => function ($value, array &$env) {
                $env['CONTENT_LENGTH'] = $value;
            },
            self::OPTION_IP =>             function ($value, array &$env) {
                if ($value) {
                    $env['REMOTE_ADDR'] = $value;
                }
            },
            self::OPTION_IS_LOCAL =>       function ($value, array &$env) {
                if ($value) {
                    $env['REMOTE_ADDR'] = '::1';
                }
            },
            self::OPTION_IS_DELETE =>      function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_DELETE;
                }
            },
            self::OPTION_IS_CONNECT =>     function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_CONNECT;
                }
            },
            self::OPTION_IS_GET =>         function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_GET;
                }
            },
            self::OPTION_IS_HEAD =>        function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_HEAD;
                }
            },
            self::OPTION_IS_OPTIONS =>     function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_OPTIONS;
                }
            },
            self::OPTION_IS_PATCH =>       function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_PATCH;
                }
            },
            self::OPTION_IS_POST =>        function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_POST;
                }
            },
            self::OPTION_IS_PUT =>         function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_PUT;
                }
            },
            self::OPTION_IS_TRACE =>       function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = Request::METHOD_TRACE;
                }
            },
            self::OPTION_IS_XHR =>         function ($value, array &$env) {
                if ($value) {
                    $env['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
                } else {
                    unset($env['HTTP_X_REQUESTED_WITH']);
                }
            },
            self::OPTION_METHOD =>         function ($value, array &$env) {
                if ($value) {
                    $env['REQUEST_METHOD'] = $value;
                }
            },
            self::OPTION_PATH =>           function ($value, array &$env) {
                $env['REQUEST_URI'] = $value;
            }, // TODO-20130521: handle query string
            self::OPTION_REFERER =>        function ($value, array &$env) {
                $env['HTTP_REFERER'] = $value;
            },
            self::OPTION_URI =>            function ($value, array &$env) {
                $env['REQUEST_URI'] = $value;
                $qs = strpos($value, '?');
                $env['QUERY_STRING'] = $qs === false ? '' : substr($value, $qs + 1);
            },
            self::OPTION_USER_AGENT =>     function ($value, array &$env) {
                $env['HTTP_USER_AGENT'] = $value;
            }

        ];
    }
}
