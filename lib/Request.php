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

use ICanBoogie\Accessor\AccessorTrait;
use InvalidArgumentException;

use function ICanBoogie\normalize_url_path;

/**
 * An HTTP request.
 *
 * ```php
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
 * ```
 *
 * @method Response connect(array $params = null)
 * @method Response delete(array $params = null)
 * @method Response get(array $params = null)
 * @method Response head(array $params = null)
 * @method Response options(array $params = null)
 * @method Response post(array $params = null)
 * @method Response put(array $params = null)
 * @method Response patch(array $params = null)
 * @method Response trace(array $params = null)
 *
 * @property-read Request\Context $context the request's context.
 * @property-read FileList $files The files associated with the request.
 *
 * @property-read bool $authorization Authorization of the request.
 * @property-read int $content_length Length of the request content.
 * @property-read string $ip Remote IP of the request.
 * @property-read bool $is_local Is this a local request?
 * @property-read bool $is_xhr Is this an Ajax request?
 * @property-read RequestMethod $method Method of the request.
 * @property-read string $normalized_path Path of the request normalized using the
 *     `\ICanBoogie\normalize_url_path` function.
 * @property-read string $path Path info of the request.
 * @property-read string $extension The extension of the path.
 * @property-read int $port Port of the request.
 * @property-read string $query_string Query string of the request.
 * @property-read string $script_name Name of the entered script.
 * @property-read string $referer Referer of the request.
 * @property-read string $user_agent User agent of the request.
 * @property-read string $uri URI of the request. The `QUERY_STRING` value of the environment
 * is overwritten when the instance is created with the `$uri` property.
 *
 * @see http://en.wikipedia.org/wiki/Uniform_resource_locator
 */
final class Request implements RequestOptions
{
    /**
     * @uses get_context
     * @uses get_files
     * @uses get_script_name
     * @uses get_method
     * @uses get_query_string
     * @uses get_content_length
     * @uses get_referer
     * @uses get_user_agent
     * @uses get_is_xhr
     * @uses get_is_local
     * @uses get_ip
     * @uses get_authorization
     * @uses get_uri
     * @uses get_port
     * @uses get_path
     * @uses get_normalized_path
     * @uses get_extension
     * @uses lazy_get_params
     */
    use AccessorTrait;

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
     * Union of {@link $path_params}, {@link $request_params} and {@link $query_params}.
     *
     * @var array<string, mixed>
     */
    public $params;

    private Request\Context $context;

    private function get_context(): Request\Context
    {
        return $this->context;
    }

    /**
     * The headers of the request.
     */
    public Headers $headers;

    /**
     * Request environment.
     *
     * @var array<string, mixed>
     */
    private array $env;

    /**
     * Files associated with the request.
     *
     * @var FileList
     */
    private $files;

    private function get_files(): FileList
    {
        if ($this->files instanceof FileList) {
            return $this->files;
        }

        return $this->files = FileList::from($this->files); // @phpstan-ignore-line
    }

    public $cookie; // @phpstan-ignore-line

    /**
     * A request may be created from the `$_SERVER` super global array. In that case `$_SERVER` is
     * used as environment the request is created with the following properties:
     *
     * - {@link $cookie}: a reference to the `$_COOKIE` super global array.
     * - {@link $path_params}: initialized to an empty array.
     * - {@link $query_params}: a reference to the `$_GET` super global array.
     * - {@link $request_params}: a reference to the `$_POST` super global array.
     * - {@link $files}: a reference to the `$_FILES` super global array.
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
     * @param array<string, mixed> $env Environment, usually the `$_SERVER` array.
     *
     * @throws InvalidArgumentException in attempt to use an unsupported option.
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
        return self::from([

            self::OPTION_COOKIE => &$_COOKIE,
            self::OPTION_PATH_PARAMS => [],
            self::OPTION_QUERY_PARAMS => &$_GET,
            self::OPTION_REQUEST_PARAMS => &$_POST,
            self::OPTION_FILES => &$_FILES // @codeCoverageIgnore

        ], $_SERVER);
    }

    /**
     * Creates an instance from an URI.
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
            RequestOptionsMapper::map($options, $env);
        }

        if (!empty($env['QUERY_STRING'])) {
            parse_str($env['QUERY_STRING'], $options[self::OPTION_QUERY_PARAMS]);
        }

        return new self($options, $env);
    }

    /**
     * Initialize the properties {@link $env}, {@link $headers} and {@link $context}.
     *
     * If the {@link $params} property is `null` it is set with an union of {@link $path_params},
     * {@link $request_params} and {@link $query_params}.
     *
     * @phpstan-param array<string, mixed> $properties Initial properties.
     *
     * @param array<string, mixed> $env Environment of the request, usually the `$_SERVER` super global.
     *
     * @throws MethodNotAllowed when the request method is not supported.
     */
    private function __construct(array $properties, array $env = [])
    {
        $this->context = new Request\Context($this);
        $this->env = $env;

        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }

        $this->assert_method($this->method);

        $this->headers ??= new Headers($env);
        $this->params ??= $this->path_params + $this->request_params + $this->query_params; // @phpstan-ignore-line
    }

    /**
     * Clone {@link $headers} and {@link $context}, and unset {@link $params}.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->context = clone $this->context;

        unset($this->params);
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
            RequestOptionsMapper::map($options, $changed->env);

            foreach ($options as $option => &$value) {
                $changed->$option = $value;
            }
        }

        return $changed;
    }

    /**
     * Returns the script name.
     *
     * The setter is volatile, the value is returned from the ENV key `SCRIPT_NAME`.
     */
    private function get_script_name(): string
    {
        return $this->env['SCRIPT_NAME'];
    }

    /**
     * Returns the request method.
     *
     * This is the getter for the `method` magic property.
     *
     * The method is retrieved from {@link $env}, if the key `REQUEST_METHOD` is not defined,
     * the method defaults to {@link METHOD_GET}.
     */
    private function get_method(): RequestMethod
    {
        $method = RequestMethod::from_mixed($this->env['REQUEST_METHOD'] ?? 'GET');

        if ($method === RequestMethod::METHOD_POST && !empty($this->request_params['_method'])) {
            $method = RequestMethod::from_mixed($this->request_params['_method']);
        }

        return $method;
    }

    /**
     * Returns the query string of the request.
     *
     * The value is obtained from the `QUERY_STRING` key of the {@link $env} array.
     */
    private function get_query_string(): ?string
    {
        return $this->env['QUERY_STRING'] ?? null;
    }

    /**
     * Returns the content length of the request.
     *
     * The value is obtained from the `CONTENT_LENGTH` key of the {@link $env} array.
     */
    private function get_content_length(): ?int
    {
        return $this->env['CONTENT_LENGTH'] ?? null;
    }

    /**
     * Returns the referer of the request.
     *
     * The value is obtained from the `HTTP_REFERER` key of the {@link $env} array.
     */
    private function get_referer(): ?string
    {
        return $this->env['HTTP_REFERER'] ?? null;
    }

    /**
     * Returns the user agent of the request.
     *
     * The value is obtained from the `HTTP_USER_AGENT` key of the {@link $env} array.
     *
     * @return string|null
     */
    private function get_user_agent(): ?string
    {
        return $this->env['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Checks if the request is a `XMLHTTPRequest`.
     */
    private function get_is_xhr(): bool
    {
        return !empty($this->env['HTTP_X_REQUESTED_WITH'])
            && str_contains($this->env['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest');
    }

    /**
     * Checks if the request is local.
     */
    private function get_is_local(): bool
    {
        $ip = $this->ip;

        if ($ip == '::1' || preg_match('/^127\.0\.0\.\d{1,3}$/', $ip)) {
            return true;
        }

        return preg_match('/^0:0:0:0:0:0:0:1(%.*)?$/', $ip);
    }

    /**
     * Returns the remote IP of the request.
     *
     * If defined, the `HTTP_X_FORWARDED_FOR` header is used to retrieve the original IP.
     *
     * If the `REMOTE_ADDR` header is empty the request is considered local thus `::1` is returned.
     *
     * @see http://en.wikipedia.org/wiki/X-Forwarded-For
     */
    private function get_ip(): string
    {
        $forwarded_for = $this->headers['X-Forwarded-For'];

        if ($forwarded_for) {
            [ $ip ] = explode(',', $forwarded_for);

            return $ip;
        }

        return $this->env['REMOTE_ADDR'] ?? '::1';
    }

    private function get_authorization(): ?string
    {
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

    /**
     * Returns the `REQUEST_URI` environment key.
     *
     * If the `REQUEST_URI` key is not defined by the environment, the value is fetched from
     * the `$_SERVER` array. If the key is not defined in the `$_SERVER` array `null` is returned.
     */
    private function get_uri(): ?string
    {
        return $this->env['REQUEST_URI'] ?? ($_SERVER['REQUEST_URI'] ?? null);
    }

    /**
     * Returns the port of the request.
     */
    private function get_port(): int
    {
        return $this->env['REQUEST_PORT'];
    }

    /**
     * Returns the path of the request, that is the `REQUEST_URI` without the query string.
     */
    private function get_path(): string
    {
        $uri = $this->uri;
        $qs_pos = strpos($uri, '?');

        return ($qs_pos === false) ? $uri : substr($uri, 0, $qs_pos);
    }

    /**
     * Returns the {@link $path} property normalized using the
     * `ICanBoogie\normalize_url_path()` function.
     */
    private function get_normalized_path(): string
    {
        return normalize_url_path($this->path);
    }

    /**
     * Returns the extension of the path info.
     *
     * @return mixed
     */
    private function get_extension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function lazy_set_params(array $params): array
    {
        return $params;
    }

    /**
     * Returns the union of the {@link path_params}, {@link request_params} and
     * {@link query_params} properties.
     *
     * This method is the getter of the {@link $params} magic property.
     *
     * @return array<string|int, mixed>
     */
    private function lazy_get_params(): array
    {
        return $this->path_params + $this->request_params + $this->query_params;
    }
}
