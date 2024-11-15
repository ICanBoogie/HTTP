<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\HTTP\Headers\ContentType;
use InvalidArgumentException;

use function file_get_contents;
use function ICanBoogie\normalize_url_path;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * An HTTP request.
 *
 * <pre>
 * <?php
 *
 * use ICanBoogie\HTTP\Request;
 *
 * # Creating the main request
 *
 * $request = Request::from($_SERVER);
 *
 * # Creating a request from scratch, with the current environment.
 *
 * $request = Request::from([
 *
 *     Request::OPTION_URI => '/path/to/my/page.html?page=2',
 *     Request::OPTION_USER_AGENT => 'Mozilla'
 *     Request::OPTION_IS_GET => true,
 *     Request::OPTION_IS_XHR => true,
 *     Request::OPTION_IS_LOCAL => true
 *
 * ], $_SERVER);
 * </pre>
 *
 * @link https://en.wikipedia.org/wiki/Uniform_resource_locator
 */
final class Request implements RequestOptions
{
    /**
     * Parameters extracted from the request path.
     *
     * @var array<int|string, mixed>
     */
    public array $path_params = [];

    /**
     * Parameters defined by the query string.
     *
     * @var array<string, mixed>
     */
    public array $query_params = [];

    /**
     * Parameters defined by the request body.
     *
     * @var array<string, mixed>
     */
    public mixed $request_params = [];

    /**
     * Union of {@see $path_params}, {@see $request_params} and {@see $query_params}.
     *
     * **Note**: The property is created during construct and is not updated after. If you modify one of
     * {@see $path_params}, {@see $request_params} and {@see $query_params}, remember to modify {@see $params} as
     * well.
     *
     * @var array<string, mixed>
     */
    public array $params;

    public readonly Request\Context $context;
    public readonly Headers $headers;

    /**
     * Request environment.
     *
     * @var array<string, mixed>
     */
    private array $env;

    /**
     * Files associated with the request.
     */
    // The field is not readonly because it can be overwritten by `with()`.
    private(set) FileList $files;

    public $cookie;

    /**
     * A request may be created from the `$_SERVER` super global array. In that case `$_SERVER` is
     * used as environment, the request is created with the following properties:
     *
     * - {@see $cookie}: a reference to the `$_COOKIE` super global.
     * - {@see $path_params}: initialized to an empty array.
     * - {@see $query_params}: a reference to the `$_GET` super global.
     * - {@see $request_params}: a reference to the `$_POST` super global.
     * - {@see $files}: a reference to the `$_FILES` super global.
     *
     * A request may also be created from an array of properties, in which case most of them are
     * mapped to the `$env` constructor param. For instance, `is_xhr` set the
     * `HTTP_X_REQUESTED_WITH` environment property to 'XMLHttpRequest'. In fact, only the
     * following options are preserved:
     *
     * - Request::OPTION_PATH_PARAMS
     * - Request::OPTION_QUERY_PARAMS
     * - Request::OPTION_REQUEST_PARAMS
     * - Request::OPTION_FILES: The files associated with the request.
     * - Request::OPTION_HEADERS: The header fields of the request. If specified, the headers
     * available in the environment are ignored.
     *
     * @phpstan-param array<RequestOptions::*, mixed>|string|null $properties Properties of the request.
     *
     * @param array<string, mixed> $env Environment, usually the `$_SERVER` array.
     *
     * @throws InvalidArgumentException in an attempt to use an unsupported option.
     */
    public static function from(array|string|null $properties = null, array $env = []): self
    {
        if (!$properties) {
            return new self([], $env);
        }

        if ($properties === $_SERVER) {
            return self::from_server();
        }

        if (is_string($properties)) {
            return self::from_uri($properties, $env);
        }

        return self::from_options($properties, $env);
    }

    /**
     * Creates an instance from the `$_SERVER` array.
     */
    private static function from_server(): self
    {
        $content_type = isset($_SERVER['HTTP_CONTENT_TYPE'])
            ? new ContentType($_SERVER['HTTP_CONTENT_TYPE'])
            : null;

        if ($content_type?->type === 'application/json') {
            $json = file_get_contents('php://input');
            $request_params = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } else {
            $request_params = &$_POST;
        }

        return self::from([

            self::OPTION_COOKIE => &$_COOKIE,
            self::OPTION_PATH_PARAMS => [],
            self::OPTION_QUERY_PARAMS => &$_GET,
            self::OPTION_REQUEST_PARAMS => $request_params,
            self::OPTION_FILES => &$_FILES, // @codeCoverageIgnore

        ], $_SERVER);
    }

    /**
     * Creates an instance from a URI.
     *
     * @param array<string, mixed> $env
     */
    private static function from_uri(string $uri, array $env): self
    {
        return self::from([ self::OPTION_URI => $uri ], $env);
    }

    /**
     * Creates an instance from an array of properties.
     *
     * @param array<RequestOptions::*, mixed> $options
     * @param array<string, mixed> $env
     *
     * @throws MethodNotAllowed
     */
    private static function from_options(array $options, array $env): self
    {
        if ($options) {
            $options = RequestOptionsMapper::map($options, $env);
        }

        if (!empty($env['QUERY_STRING'])) {
            parse_str($env['QUERY_STRING'], $options[self::OPTION_QUERY_PARAMS]);
        }

        return new self($options, $env);
    }

    /**
     * Initialize the properties {@see $env}, {@see $headers} and {@see $context}.
     *
     * If the {@see $params} property is `null` it is set with a union of {@see $path_params},
     * {@see $request_params} and {@see $query_params}.
     *
     * @phpstan-param array<string, mixed> $options Initial properties.
     *
     * @param array<string, mixed> $env Environment of the request, usually the `$_SERVER` super global.
     *
     * @throws MethodNotAllowed when the request method is not supported.
     */
    private function __construct(array $options, array $env = [])
    {
        $this->context = new Request\Context($this);
        $this->env = $env;
        $this->headers = $options[self::OPTION_HEADERS] ?? new Headers($env);
        $this->files = $options[self::OPTION_FILES] ?? new FileList();
        $this->path_params = $options[self::OPTION_PATH_PARAMS] ?? [];
        $this->query_params = $options[self::OPTION_QUERY_PARAMS] ?? [];
        $this->request_params = $options[self::OPTION_REQUEST_PARAMS] ?? [];
        $this->params = $this->path_params + $this->request_params + $this->query_params;
        $this->cookie = $options[self::OPTION_COOKIE] ?? null;

        $this->assert_method($this->method);
    }

    /**
     * Clone {@see $headers} and {@see $context}, and unset {@see $params}.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->context = clone $this->context;
    }

    /**
     * Asserts that a method is supported.
     */
    private function assert_method(RequestMethod $method): void
    {
    }

    /**
     * Returns a new instance with the specified changed properties.
     *
     * @param array<RequestOptions::*, mixed> $options
     */
    public function with(array $options): self
    {
        $changed = clone $this;

        if ($options) {
            $options = RequestOptionsMapper::map($options, $changed->env);

            foreach ($options as $option => &$value) {
                $changed->$option = $value;
            }
        }

        $changed->params = $changed->path_params + $changed->request_params + $changed->query_params;

        return $changed;
    }

    /**
     * The script name.
     *
     * The value is returned from the ENV key `SCRIPT_NAME`.
     */
    public string $script_name {
        get => $this->env['SCRIPT_NAME'];
    }

    /**
     * The request method.
     *
     * This is the getter for the `method` magic property.
     *
     * The method is retrieved from {@see $env}, if the key `REQUEST_METHOD` is not defined,
     * the method defaults to {@see METHOD_GET}.
     */
    public RequestMethod $method
        {
            get {
                $method = RequestMethod::from_mixed($this->env['REQUEST_METHOD'] ?? 'GET');

                if ($method === RequestMethod::METHOD_POST && !empty($this->request_params['_method'])) {
                    $method = RequestMethod::from_mixed($this->request_params['_method']);
                }

                return $method;
            }
        }

    /**
     * The query string of the request.
     *
     * The value is obtained from the `QUERY_STRING` key of the {@see $env} array.
     */
    public ?string $query_string {
        get => $this->env['QUERY_STRING'] ?? null;
    }

    /**
     * The content length of the request.
     *
     * The value is obtained from the `CONTENT_LENGTH` key of the {@see $env} array.
     */
    public ?int $content_length {
        get => $this->env['CONTENT_LENGTH'] ?? null;
    }

    /**
     * The referer of the request.
     *
     * The value is obtained from the `HTTP_REFERER` key of the {@see $env} array.
     */
    public ?string $referer {
        get => $this->env['HTTP_REFERER'] ?? null;
    }

    /**
     * The user agent of the request.
     *
     * The value is obtained from the `HTTP_USER_AGENT` key of the {@see $env} array.
     */
    public ?string $user_agent {
        get => $this->env['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Checks if the request is a `XMLHTTPRequest`.
     */
    public bool $is_xhr {
        get {
            return !empty($this->env['HTTP_X_REQUESTED_WITH'])
                && str_contains($this->env['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest');
        }
    }

    /**
     * Checks if the request is local.
     */
    public bool $is_local {
        get {
            $ip = $this->ip;

            if ($ip == '::1' || preg_match('/^127\.0\.0\.\d{1,3}$/', $ip)) {
                return true;
            }

            return preg_match('/^0:0:0:0:0:0:0:1(%.*)?$/', $ip);
        }
    }

    /**
     * The remote IP of the request.
     *
     * If defined, the `HTTP_X_FORWARDED_FOR` header is used to retrieve the original IP.
     *
     * If the `REMOTE_ADDR` header is empty, the request is considered local; thus `::1` is returned.
     *
     * @link https://en.wikipedia.org/wiki/X-Forwarded-For
     */
    public string $ip {
        get {
            $forwarded_for = $this->headers['X-Forwarded-For'];

            if ($forwarded_for) {
                [ $ip ] = explode(',', $forwarded_for);

                return $ip;
            }

            return $this->env['REMOTE_ADDR'] ?? '::1';
        }
    }

    /**
     * Authorization of the request.
     */
    public ?string $authorization {
        get {
            if (isset($this->env['HTTP_AUTHORIZATION'])) {
                return $this->env['HTTP_AUTHORIZATION'];
            } elseif (isset($this->env['X-HTTP_AUTHORIZATION'])) {
                return $this->env['X-HTTP_AUTHORIZATION'];
            } elseif (isset($this->env['X_HTTP_AUTHORIZATION'])) {
                return $this->env['X_HTTP_AUTHORIZATION'];
            } elseif (isset($this->env['REDIRECT_X_HTTP_AUTHORIZATION'])) {
                return $this->env['REDIRECT_X_HTTP_AUTHORIZATION'];
            }

            return null;
        }
    }

    /**
     * Returns the `REQUEST_URI` environment key.
     *
     * If the `REQUEST_URI` key is not defined by the environment, the value is fetched from
     * the `$_SERVER` array. If the key is not defined in the `$_SERVER` array `null` is returned.
     */
    public ?string $uri {
        get => $this->env['REQUEST_URI'] ?? ($_SERVER['REQUEST_URI'] ?? null);
    }

    /**
     * The port of the request.
     */
    public int $port {
        get => $this->env['REQUEST_PORT'];
    }

    /**
     * Returns the path of the request, that is the `REQUEST_URI` without the query string.
     */
    public string $path {
        get {
            $uri = $this->uri;
            $qs_pos = strpos($uri, '?');

            return ($qs_pos === false) ? $uri : substr($uri, 0, $qs_pos);
        }
    }

    /**
     * The {@see $path} property normalized using the {@see normalize_url_path()} function.
     */
    public string $normalized_path {
        get => normalize_url_path($this->path);
    }

    /**
     * The extension of the path info.
     */
    public string $extension {
        get => pathinfo($this->path, PATHINFO_EXTENSION);
    }
}
