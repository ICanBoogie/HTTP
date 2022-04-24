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

use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;

/**
 * Representation of a response status.
 *
 * @property int $code HTTP Status code.
 * @property string $message Status message.
 *
 * @property-read bool $is_cacheable Whether the status is cacheable.
 * @property-read bool $is_client_error Whether the status is a client error.
 * @property-read bool $is_empty Whether the status is empty.
 * @property-read bool $is_forbidden Whether the status is forbidden.
 * @property-read bool $is_informational Whether the status is informational.
 * @property-read bool $is_not_found Whether the status is not found.
 * @property-read bool $is_ok Whether the status is ok.
 * @property-read bool $is_redirect Whether the status is a redirection.
 * @property-read bool $is_server_error Whether the status is a server error.
 * @property-read bool $is_successful Whether the status is successful.
 * @property-read bool $is_valid Whether the status is valid.
 */
final class Status
{
    use AccessorTrait;

    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CONTINUE
     */
    public const CONTINUE_ = 100;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SWITCHING_PROTOCOLS
     */
    public const SWITCHING_PROTOCOLS = 101;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_OK
     */
    public const OK = 200;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CREATED
     */
    public const CREATED = 201;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_ACCEPTED
     */
    public const ACCEPTED = 202;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NON_AUTHORITATIVE_INFORMATION
     */
    public const NON_AUTHORITATIVE_INFORMATION = 203;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NO_CONTENT
     */
    public const NO_CONTENT = 204;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_RESET_CONTENT
     */
    public const RESET_CONTENT = 205;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PARTIAL_CONTENT
     */
    public const PARTIAL_CONTENT = 206;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_MULTIPLE_CHOICES
     */
    public const MULTIPLE_CHOICES = 300;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_MOVED_PERMANENTLY
     */
    public const MOVED_PERMANENTLY = 301;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_FOUND
     */
    public const FOUND = 302;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SEE_OTHER
     */
    public const SEE_OTHER = 303;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_MODIFIED
     */
    public const NOT_MODIFIED = 304;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_USE_PROXY
     */
    public const USE_PROXY = 305;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_TEMPORARY_REDIRECT
     */
    public const TEMPORARY_REDIRECT = 307;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_BAD_REQUEST
     */
    public const BAD_REQUEST = 400;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_UNAUTHORIZED
     */
    public const UNAUTHORIZED = 401;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PAYMENT_REQUIRED
     */
    public const PAYMENT_REQUIRED = 402;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_FORBIDDEN
     */
    public const FORBIDDEN = 403;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_FOUND
     */
    public const NOT_FOUND = 404;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_METHOD_NOT_ALLOWED
     */
    public const METHOD_NOT_ALLOWED = 405;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_ACCEPTABLE
     */
    public const NOT_ACCEPTABLE = 406;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PROXY_AUTHENTICATION_REQUIRED
     */
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_TIMEOUT
     */
    public const REQUEST_TIMEOUT = 408;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CONFLICT
     */
    public const CONFLICT = 409;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_GONE
     */
    public const GONE = 410;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_LENGTH_REQUIRED
     */
    public const LENGTH_REQUIRED = 411;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PRECONDITION_FAILED
     */
    public const PRECONDITION_FAILED = 412;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_ENTITY_TOO_LARGE
     */
    public const REQUEST_ENTITY_TOO_LARGE = 413;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_URI_TOO_LONG
     */
    public const REQUEST_URI_TOO_LONG = 414;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_UNSUPPORTED_MEDIA_TYPE
     */
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE
     */
    public const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_EXPECTATION_FAILED
     */
    public const EXPECTATION_FAILED = 417;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_I_M_A_TEAPOT
     */
    public const I_M_A_TEAPOT = 418;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_INTERNAL_SERVER_ERROR
     */
    public const INTERNAL_SERVER_ERROR = 500;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_IMPLEMENTED
     */
    public const NOT_IMPLEMENTED = 501;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_BAD_GATEWAY
     */
    public const BAD_GATEWAY = 502;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SERVICE_UNAVAILABLE
     */
    public const SERVICE_UNAVAILABLE = 503;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_GATEWAY_TIMEOUT
     */
    public const GATEWAY_TIMEOUT = 504;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_HTTP_VERSION_NOT_SUPPORTED
     */
    public const HTTP_VERSION_NOT_SUPPORTED = 505;

    /**
     * HTTP status codes and messages.
     *
     * @var array<int, string>
     */
    public const CODES_AND_MESSAGES = [

        100 => "Continue",
        101 => "Switching Protocols",

        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",

        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",

        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",

        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",

    ];

    /**
     * Creates a new instance from the provided status.
     *
     * @param array{ 0: int, 1: string }|int|string|self $status
     *
     * @return Status
     *
     * @throws InvalidArgumentException When the HTTP status code is not valid.
     */
    public static function from(array|int|string|Status $status): self
    {
        if ($status instanceof self) {
            return $status;
        }

        $message = null;

        if (is_array($status)) {
            [ $code, $message ] = $status;
        } elseif (is_numeric($status)) {
            $code = (int) $status;
        } else {
            if (!preg_match('/^(\d{3})\s+(.+)$/', $status, $matches)) {
                throw new InvalidArgumentException("Invalid status: $status.");
            }

            [ , $code, $message ] = $matches;
        }

        return new self($code, $message);
    }

    /**
     * Asserts that a status code is valid.
     *
     * @throws StatusCodeNotValid if the status code is not valid.
     */
    private static function assert_code_is_valid(int $code): void
    {
        if ($code >= 100 && $code < 600) {
            return;
        }

        throw new StatusCodeNotValid($code);
    }

    /**
     * Status code.
     */
    private int $code;

    protected function set_code(int $code): void
    {
        self::assert_code_is_valid($code);

        $this->code = $code;
    }

    protected function get_code(): int
    {
        return $this->code;
    }

    /**
     * Whether the status is valid.
     *
     * A status is considered valid when its code is between 100 and 600, 100 included.
     */
    protected function get_is_valid(): bool
    {
        return $this->code >= 100 && $this->code < 600;
    }

    /**
     * Whether the status is informational.
     *
     * A status is considered informational when its code is between 100 and 200, 100 included.
     */
    protected function get_is_informational(): bool
    {
        return $this->code >= 100 && $this->code < 200;
    }

    /**
     * Whether the status is successful.
     *
     * A status is considered successful when its code is between 200 and 300, 200 included.
     */
    protected function get_is_successful(): bool
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Whether the status is a redirection.
     *
     * A status is considered to be a redirection when its code is between 300 and 400, 300
     * included.
     */
    protected function get_is_redirect(): bool
    {
        return $this->code >= 300 && $this->code < 400;
    }

    /**
     * Whether the status is a client error.
     *
     * A status is considered a client error when its code is between 400 and 500, 400
     * included.
     */
    protected function get_is_client_error(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Whether the status is a server error.
     *
     * A status is considered a server error when its code is between 500 and 600, 500
     * included.
     */
    protected function get_is_server_error(): bool
    {
        return $this->code >= 500 && $this->code < 600;
    }

    /**
     * Whether the status is ok.
     *
     * A status is considered ok when its code is {@link ResponseStatus::STATUS_OK}.
     */
    protected function get_is_ok(): bool
    {
        return $this->code == ResponseStatus::STATUS_OK;
    }

    /**
     * Whether the status is forbidden.
     *
     * A status is considered forbidden ok when its code is {@link ResponseStatus::STATUS_FORBIDDEN}.
     */
    protected function get_is_forbidden(): bool
    {
        return $this->code == ResponseStatus::STATUS_FORBIDDEN;
    }

    /**
     * Whether the status is not found.
     *
     * A status is considered not found when its code is {@link ResponseStatus::STATUS_NOT_FOUND}.
     */
    protected function get_is_not_found(): bool
    {
        return $this->code == ResponseStatus::STATUS_NOT_FOUND;
    }

    /**
     * Whether the status is empty.
     *
     * A status is considered empty when its code is {@link ResponseStatus::CREATED},
     * {@link ResponseStatus::NO_CONTENT} or {@link ResponseStatus::NOT_MODIFIED}.
     */
    protected function get_is_empty(): bool
    {
        static $range = [

            ResponseStatus::STATUS_CREATED,
            ResponseStatus::STATUS_NO_CONTENT,
            ResponseStatus::STATUS_NOT_MODIFIED,

        ];

        return in_array($this->code, $range);
    }

    /**
     * Whether the status is cacheable.
     */
    protected function get_is_cacheable(): bool
    {
        static $range = [

            ResponseStatus::STATUS_OK,
            ResponseStatus::STATUS_NON_AUTHORITATIVE_INFORMATION,
            ResponseStatus::STATUS_MULTIPLE_CHOICES,
            ResponseStatus::STATUS_MOVED_PERMANENTLY,
            ResponseStatus::STATUS_NOT_FOUND,
            ResponseStatus::STATUS_NOT_FOUND,
            ResponseStatus::STATUS_GONE,

        ];

        return in_array($this->code, $range);
    }

    /**
     * Message describing the status code.
     *
     * @var string|null
     */
    private $message;

    protected function set_message(?string $message): void
    {
        $this->message = $message;
    }

    protected function get_message(): string
    {
        $message = $this->message;
        $code = $this->code;

        if (!$message && $code) {
            $message = self::CODES_AND_MESSAGES[$code];
        }

        return $message;
    }

    public function __construct(int $code = ResponseStatus::STATUS_OK, string $message = null)
    {
        self::assert_code_is_valid($code);

        $this->code = $code;
        $this->message = $message ?: self::CODES_AND_MESSAGES[$code];
    }

    public function __toString()
    {
        return "$this->code " . $this->get_message();
    }
}
