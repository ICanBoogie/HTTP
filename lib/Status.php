<?php

namespace ICanBoogie\HTTP;

use InvalidArgumentException;

use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;

/**
 * Representation of a response status.
 */
final class Status
{
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CONTINUE
     */
    public const int CONTINUE_ = 100;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SWITCHING_PROTOCOLS
     */
    public const int SWITCHING_PROTOCOLS = 101;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_OK
     */
    public const int OK = 200;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CREATED
     */
    public const int CREATED = 201;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_ACCEPTED
     */
    public const int ACCEPTED = 202;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NON_AUTHORITATIVE_INFORMATION
     */
    public const int NON_AUTHORITATIVE_INFORMATION = 203;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NO_CONTENT
     */
    public const int NO_CONTENT = 204;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_RESET_CONTENT
     */
    public const int RESET_CONTENT = 205;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PARTIAL_CONTENT
     */
    public const int PARTIAL_CONTENT = 206;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_MULTIPLE_CHOICES
     */
    public const int MULTIPLE_CHOICES = 300;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_MOVED_PERMANENTLY
     */
    public const int MOVED_PERMANENTLY = 301;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_FOUND
     */
    public const int FOUND = 302;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SEE_OTHER
     */
    public const int SEE_OTHER = 303;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_MODIFIED
     */
    public const int NOT_MODIFIED = 304;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_USE_PROXY
     */
    public const int USE_PROXY = 305;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_TEMPORARY_REDIRECT
     */
    public const int TEMPORARY_REDIRECT = 307;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_BAD_REQUEST
     */
    public const int BAD_REQUEST = 400;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_UNAUTHORIZED
     */
    public const int UNAUTHORIZED = 401;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PAYMENT_REQUIRED
     */
    public const int PAYMENT_REQUIRED = 402;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_FORBIDDEN
     */
    public const int FORBIDDEN = 403;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_FOUND
     */
    public const int NOT_FOUND = 404;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_METHOD_NOT_ALLOWED
     */
    public const int METHOD_NOT_ALLOWED = 405;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_ACCEPTABLE
     */
    public const int NOT_ACCEPTABLE = 406;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PROXY_AUTHENTICATION_REQUIRED
     */
    public const int PROXY_AUTHENTICATION_REQUIRED = 407;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_TIMEOUT
     */
    public const int REQUEST_TIMEOUT = 408;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_CONFLICT
     */
    public const int CONFLICT = 409;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_GONE
     */
    public const int GONE = 410;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_LENGTH_REQUIRED
     */
    public const int LENGTH_REQUIRED = 411;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_PRECONDITION_FAILED
     */
    public const int PRECONDITION_FAILED = 412;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_ENTITY_TOO_LARGE
     */
    public const int REQUEST_ENTITY_TOO_LARGE = 413;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUEST_URI_TOO_LONG
     */
    public const int REQUEST_URI_TOO_LONG = 414;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_UNSUPPORTED_MEDIA_TYPE
     */
    public const int UNSUPPORTED_MEDIA_TYPE = 415;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE
     */
    public const int REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_EXPECTATION_FAILED
     */
    public const int EXPECTATION_FAILED = 417;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_I_M_A_TEAPOT
     */
    public const int I_M_A_TEAPOT = 418;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_INTERNAL_SERVER_ERROR
     */
    public const int INTERNAL_SERVER_ERROR = 500;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_NOT_IMPLEMENTED
     */
    public const int NOT_IMPLEMENTED = 501;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_BAD_GATEWAY
     */
    public const int BAD_GATEWAY = 502;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_SERVICE_UNAVAILABLE
     */
    public const int SERVICE_UNAVAILABLE = 503;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_GATEWAY_TIMEOUT
     */
    public const int GATEWAY_TIMEOUT = 504;
    /**
     * @deprecated
     * @see ResponseStatus::STATUS_HTTP_VERSION_NOT_SUPPORTED
     */
    public const int HTTP_VERSION_NOT_SUPPORTED = 505;

    /**
     * HTTP status codes and messages.
     *
     * @var array<int, string>
     */
    public const array CODES_AND_MESSAGES = [

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
            $code = (int)$status;
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
    public int $code {
        get => $this->code;
        set {
            self::assert_code_is_valid($value);

            $this->code = $value;
        }
    }

    /**
     * Whether the status is valid.
     *
     * A status is considered valid when its code is between 100 and 600, 100 included.
     */
    public bool $is_valid {
        get => $this->code >= 100 && $this->code < 600;
    }

    /**
     * Whether the status is informational.
     *
     * A status is considered informational when its code is between 100 and 200, 100 included.
     */
    public bool $is_informational {
        get => $this->code >= 100 && $this->code < 200;
    }

    /**
     * Whether the status is successful.
     *
     * A status is considered successful when its code is between 200 and 300, 200 included.
     */
    public bool $is_successful {
        get => $this->code >= 200 && $this->code < 300;
    }

    /**
     * Whether the status is a redirection.
     *
     * A status is considered to be a redirection when its code is between 300 and 400, 300
     * included.
     */
    public bool $is_redirect {
        get => $this->code >= 300 && $this->code < 400;
    }

    /**
     * Whether the status is a client error.
     *
     * A status is considered a client error when its code is between 400 and 500, 400
     * included.
     */
    public bool $is_client_error {
        get => $this->code >= 400 && $this->code < 500;
    }

    /**
     * Whether the status is a server error.
     *
     * A status is considered a server error when its code is between 500 and 600, 500
     * included.
     */
    public bool $is_server_error {
        get => $this->code >= 500 && $this->code < 600;
    }

    /**
     * Whether the status is ok.
     *
     * A status is considered ok when its code is {@see ResponseStatus::STATUS_OK}.
     */
    public bool $is_ok {
        get => $this->code == ResponseStatus::STATUS_OK;
    }

    /**
     * Whether the status is forbidden.
     *
     * A status is considered forbidden ok when its code is {@see ResponseStatus::STATUS_FORBIDDEN}.
     */
    public bool $is_forbidden {
        get => $this->code == ResponseStatus::STATUS_FORBIDDEN;
    }

    /**
     * Whether the status is not found.
     *
     * A status is considered not found when its code is {@see ResponseStatus::STATUS_NOT_FOUND}.
     */
    public bool $is_not_found {
        get => $this->code == ResponseStatus::STATUS_NOT_FOUND;
    }

    /**
     * Whether the status is empty.
     *
     * A status is considered empty when its code is {@see ResponseStatus::CREATED},
     * {@see ResponseStatus::NO_CONTENT} or {@see ResponseStatus::NOT_MODIFIED}.
     */
    public bool $is_empty {
        get {
            static $range = [

                ResponseStatus::STATUS_CREATED,
                ResponseStatus::STATUS_NO_CONTENT,
                ResponseStatus::STATUS_NOT_MODIFIED,

            ];

            return in_array($this->code, $range);
        }
    }

    /**
     * Whether the status is cacheable.
     */
    public bool $is_cacheable {
        get {
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
    }

    /**
     * Message describing the status code.
     *
     * @var string|null
     */
    public ?string $message {
        get {
            $message = $this->message;
            $code = $this->code;

            if (!$message && $code) {
                $message = self::CODES_AND_MESSAGES[$code];
            }

            return $message;
        }
        set => $this->message = $value;
    }

    public function __construct(int $code = ResponseStatus::STATUS_OK, ?string $message = null)
    {
        self::assert_code_is_valid($code);

        $this->code = $code;
        $this->message = $message ?: self::CODES_AND_MESSAGES[$code];
    }

    public function __toString()
    {
        return "$this->code " . $this->message;
    }
}
