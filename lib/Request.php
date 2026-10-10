<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use ICanBoogie\HTTP\Headers\ContentType;
use InvalidArgumentException;
use JsonException;

use function array_reverse;
use function ctype_digit;
use function explode;
use function file_get_contents;
use function filter_var;
use function get_debug_type;
use function ICanBoogie\normalize_url_path;
use function in_array;
use function inet_pton;
use function intdiv;
use function is_array;
use function json_decode;
use function ord;
use function strlen;
use function strncmp;
use function trim;

use const FILTER_VALIDATE_IP;
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
 * $request = Request::from_server();
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
     * Methods a `POST` request may emulate with the `_method` request parameter.
     */
    private const array OVERRIDABLE_METHODS = [

        RequestMethod::METHOD_PUT,
        RequestMethod::METHOD_PATCH,
        RequestMethod::METHOD_DELETE,

    ];

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
    public array $request_params = [];

    /**
     * Union of {@see $path_params}, {@see $request_params} and {@see $query_params}.
     *
     * The union is computed on read, so it always reflects the three arrays. On a key conflict,
     * the path parameters win over the request body, which wins over the query string. Note that
     * this differs from the default order of `$_REQUEST`.
     *
     * @var array<string, mixed>
     */
    public array $params {
        get => $this->path_params + $this->request_params + $this->query_params;
    }

    public readonly Request\Context $context;

    // The field is not readonly because it can be overwritten by `with()`.
    private(set) Headers $headers;

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

    /**
     * The cookies of the request, usually a reference to the `$_COOKIE` super global.
     *
     * Cookies are not otherwise implemented: there is no parsing of the `Cookie` header field, and
     * no API to set cookies on a response.
     *
     * @var array<string, string>|null
     */
    public ?array $cookie;

    /**
     * {@see from_server()} creates a request from the `$_SERVER` super global array (passing `$_SERVER` to this method throws a
     * `BadMethodCallException`). `$_SERVER` is used as environment, and the request is created with the following properties:
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
            throw new \BadMethodCallException("Use Request::from_server() to create a request from \$_SERVER");
        }

        if (is_string($properties)) {
            return self::from_uri($properties, $env);
        }

        return self::from_options($properties, $env);
    }

    /**
     * Creates an instance from the `$_SERVER` array.
     *
     * @param array<string, mixed>|null $server
     */
    public static function from_server(?array $server = null): self
    {
        $server ??= $_SERVER;

        // CGI exposes `CONTENT_TYPE` without the `HTTP_` prefix.
        $content_type = $server['CONTENT_TYPE'] ?? $server['HTTP_CONTENT_TYPE'] ?? null;
        $content_type = $content_type ? new ContentType($content_type) : null;

        if ($content_type?->type === 'application/json') {
            $request_params = self::decode_json_body((string) file_get_contents('php://input'));
        } else {
            $request_params = &$_POST;
        }

        return self::from([

            self::OPTION_COOKIE => &$_COOKIE,
            self::OPTION_PATH_PARAMS => [],
            self::OPTION_QUERY_PARAMS => &$_GET,
            self::OPTION_REQUEST_PARAMS => $request_params,
            self::OPTION_FILES => &$_FILES, // @codeCoverageIgnore

        ], $server);
    }

    /**
     * Decodes a JSON request body into request parameters.
     *
     * An empty body results in no parameters.
     *
     * @return array<string, mixed>
     *
     * @throws ClientError if the body is not valid JSON, or is not a JSON object or array.
     */
    private static function decode_json_body(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        try {
            $params = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientError("Malformed JSON in request body: {$e->getMessage()}.", previous: $e);
        }

        if (!is_array($params)) {
            throw new ClientError("Expected a JSON object or array in request body, got: " . get_debug_type($params) . ".");
        }

        return $params;
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
        $this->headers = $options[self::OPTION_HEADERS] ?? Headers::from_server($env);
        $this->files = $options[self::OPTION_FILES] ?? new FileList();
        $this->path_params = $options[self::OPTION_PATH_PARAMS] ?? [];
        $this->query_params = $options[self::OPTION_QUERY_PARAMS] ?? [];
        $this->request_params = $options[self::OPTION_REQUEST_PARAMS] ?? [];
        $this->cookie = $options[self::OPTION_COOKIE] ?? null;
    }

    /**
     * Clone {@see $headers} and {@see $context}.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->context = clone $this->context;
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

            foreach ($options as $option => $value) {
                match ($option) {
                    self::OPTION_PATH_PARAMS => $changed->path_params = $value,
                    self::OPTION_QUERY_PARAMS => $changed->query_params = $value,
                    self::OPTION_REQUEST_PARAMS => $changed->request_params = $value,
                    self::OPTION_COOKIE => $changed->cookie = $value,
                    self::OPTION_FILES => $changed->files = $value,
                    self::OPTION_HEADERS => $changed->headers = $value,
                };
            }
        }

        return $changed;
    }

    /**
     * The script name.
     *
     * The value is returned from the ENV key `SCRIPT_NAME`, or an empty string if it's not defined.
     */
    public string $script_name {
        get => $this->env['SCRIPT_NAME'] ?? '';
    }

    /**
     * The request method.
     *
     * This is the getter for the `method` magic property.
     *
     * The method is retrieved from {@see $env}, if the key `REQUEST_METHOD` is not defined,
     * the method defaults to {@see RequestMethod::METHOD_GET}.
     *
     * A `POST` request can emulate `PUT`, `PATCH` or `DELETE` with a `_method` request
     * parameter. Other values are ignored.
     */
    public RequestMethod $method
        {
            get {
                $method = RequestMethod::from_mixed($this->env['REQUEST_METHOD'] ?? 'GET');

                if ($method === RequestMethod::METHOD_POST && !empty($this->request_params['_method'])) {
                    $override = RequestMethod::from_mixed($this->request_params['_method']);

                    if (in_array($override, self::OVERRIDABLE_METHODS, true)) {
                        $method = $override;
                    }
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
        get => isset($this->env['CONTENT_LENGTH']) ? (int) $this->env['CONTENT_LENGTH'] : null;
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
     *
     * The check uses {@see $ip}, that is the address of the peer, never a forwarding header.
     */
    public bool $is_local {
        get {
            $ip = $this->ip;

            return $ip === '::1'
                || preg_match('/^127\.0\.0\.\d{1,3}$/', $ip) === 1
                || preg_match('/^0:0:0:0:0:0:0:1(%.*)?$/', $ip) === 1;
        }
    }

    /**
     * The IP of the peer that connected to the server, obtained from the `REMOTE_ADDR` key of the
     * {@see $env} array.
     *
     * Forwarding headers such as `X-Forwarded-For` are ignored because any client can send them.
     * Use {@see client_ip()} when the application runs behind trusted proxies.
     *
     * If `REMOTE_ADDR` is not defined, the request is considered local; thus `::1` is returned.
     */
    public string $ip {
        get => $this->env['REMOTE_ADDR'] ?? '::1';
    }

    /**
     * Returns the IP of the client, taking `X-Forwarded-For` into account when the request comes
     * from a trusted proxy.
     *
     * If {@see $ip} is not a trusted proxy, it is returned as is. Otherwise, `X-Forwarded-For` is
     * read from right to left, skipping trusted proxies, and the first address that isn't one is
     * returned. The leftmost entries are set by the client and cannot be trusted, which is why the
     * list is not read from the left. Reading stops at the first invalid entry.
     *
     * @param string[] $trusted_proxies IP addresses or CIDR ranges, such as `10.0.0.0/8` or `fd00::/8`.
     *
     * @throws InvalidArgumentException if a trusted proxy is not a valid IP address or CIDR range.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/X-Forwarded-For
     */
    public function client_ip(array $trusted_proxies): string
    {
        $ip = $this->ip;

        if (!self::ip_in_ranges($ip, $trusted_proxies)) {
            return $ip;
        }

        $forwarded_for = (string) $this->headers['X-Forwarded-For'];

        if ($forwarded_for === '') {
            return $ip;
        }

        foreach (array_reverse(explode(',', $forwarded_for)) as $hop) {
            $hop = trim($hop);

            if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                break;
            }

            $ip = $hop;

            if (!self::ip_in_ranges($hop, $trusted_proxies)) {
                break;
            }
        }

        return $ip;
    }

    /**
     * Whether an IP matches one of the IP addresses or CIDR ranges.
     *
     * @param string[] $ranges
     *
     * @throws InvalidArgumentException if a range is not a valid IP address or CIDR range.
     */
    private static function ip_in_ranges(string $ip, array $ranges): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $address = inet_pton($ip);

        foreach ($ranges as $range) {
            [ $subnet, $bits ] = explode('/', $range, 2) + [ 1 => null ];

            if (filter_var($subnet, FILTER_VALIDATE_IP) === false) {
                throw new InvalidArgumentException("Not a valid IP address or CIDR range: $range");
            }

            $subnet = inet_pton($subnet);
            $max_bits = strlen($subnet) * 8;

            if ($bits === null) {
                $bits = $max_bits;
            } elseif (!ctype_digit($bits) || $bits > $max_bits) {
                throw new InvalidArgumentException("Not a valid IP address or CIDR range: $range");
            } else {
                $bits = (int) $bits;
            }

            if (strlen($address) !== strlen($subnet)) {
                continue;
            }

            $bytes = intdiv($bits, 8);

            if (strncmp($address, $subnet, $bytes) !== 0) {
                continue;
            }

            $remaining_bits = $bits % 8;

            if ($remaining_bits === 0) {
                return true;
            }

            $mask = (0xff << (8 - $remaining_bits)) & 0xff;

            if ((ord($address[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Authorization of the request.
     *
     * Apache drops the `Authorization` header unless it's passed along by a rewrite rule, which
     * defines `HTTP_AUTHORIZATION`, or one of the variants checked here, depending on the
     * configuration (`X-HTTP_AUTHORIZATION`, `X_HTTP_AUTHORIZATION`, or the `REDIRECT_` prefixed
     * one when the request went through an internal redirect).
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
     * Returns the `REQUEST_URI` environment key, or `null` if it's not defined.
     */
    public ?string $uri {
        get => $this->env['REQUEST_URI'] ?? null;
    }

    /**
     * The port of the request.
     *
     * The value is obtained from the `SERVER_PORT` key of the {@see $env} array, or `REQUEST_PORT`
     * (the key used by previous versions). Defaults to 80.
     */
    public int $port {
        get => (int) ($this->env['SERVER_PORT'] ?? $this->env['REQUEST_PORT'] ?? 80);
    }

    /**
     * Returns the path of the request, that is the `REQUEST_URI` without the query string, or an
     * empty string if the request has no URI.
     */
    public string $path {
        get {
            $uri = $this->uri ?? '';
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
