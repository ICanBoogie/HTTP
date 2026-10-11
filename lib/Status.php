<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use function in_array;

/**
 * Representation of a response status.
 *
 * Instances are immutable and obtained with {@see from()}, or with one of the `of_*()` helpers
 * for the most used codes. Custom codes and reasons are supported, as long as the code is between
 * 100 and 599: `Status::from(499, 'Client Closed Request')`.
 *
 * Statuses are compared by value (`==`), the reason included. Compare the `code` property to
 * ignore the reason. Instances without a custom reason are shared, but identity (`===`) must not be
 * relied upon.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 */
final class Status
{
    private const int MIN = 100;
    private const int MAX = 599;

    public const int CONTINUE = 100;
    public const int SWITCHING_PROTOCOLS = 101;
    public const int PROCESSING = 102;
    public const int EARLY_HINTS = 103;
    public const int OK = 200;
    public const int CREATED = 201;
    public const int ACCEPTED = 202;
    public const int NON_AUTHORITATIVE_INFORMATION = 203;
    public const int NO_CONTENT = 204;
    public const int RESET_CONTENT = 205;
    public const int PARTIAL_CONTENT = 206;
    public const int MULTI_STATUS = 207;
    public const int ALREADY_REPORTED = 208;
    public const int IM_USED = 226;
    public const int MULTIPLE_CHOICES = 300;
    public const int MOVED_PERMANENTLY = 301;
    public const int FOUND = 302;
    public const int SEE_OTHER = 303;
    public const int NOT_MODIFIED = 304;
    public const int TEMPORARY_REDIRECT = 307;
    public const int PERMANENT_REDIRECT = 308;
    public const int BAD_REQUEST = 400;
    public const int UNAUTHORIZED = 401;
    public const int PAYMENT_REQUIRED = 402;
    public const int FORBIDDEN = 403;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;
    public const int NOT_ACCEPTABLE = 406;
    public const int PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int REQUEST_TIMEOUT = 408;
    public const int CONFLICT = 409;
    public const int GONE = 410;
    public const int LENGTH_REQUIRED = 411;
    public const int PRECONDITION_FAILED = 412;
    public const int REQUEST_ENTITY_TOO_LARGE = 413;
    public const int REQUEST_URI_TOO_LONG = 414;
    public const int UNSUPPORTED_MEDIA_TYPE = 415;
    public const int REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const int EXPECTATION_FAILED = 417;
    public const int IM_A_TEAPOT = 418;
    public const int MISDIRECTED_REQUEST = 421;
    public const int UNPROCESSABLE_ENTITY = 422;
    public const int LOCKED = 423;
    public const int FAILED_DEPENDENCY = 424;
    public const int TOO_EARLY = 425;
    public const int UPGRADE_REQUIRED = 426;
    public const int PRECONDITION_REQUIRED = 428;
    public const int TOO_MANY_REQUESTS = 429;
    public const int REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const int UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const int INTERNAL_SERVER_ERROR = 500;
    public const int NOT_IMPLEMENTED = 501;
    public const int BAD_GATEWAY = 502;
    public const int SERVICE_UNAVAILABLE = 503;
    public const int GATEWAY_TIMEOUT = 504;
    public const int HTTP_VERSION_NOT_SUPPORTED = 505;
    public const int VARIANT_ALSO_NEGOTIATES = 506;
    public const int INSUFFICIENT_STORAGE = 507;
    public const int LOOP_DETECTED = 508;
    public const int NOT_EXTENDED = 510;
    public const int NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Reasons of the standard status codes.
     */
    private const array REASONS = [

        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',

    ];

    /**
     * Status codes that are heuristically cacheable by default.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#section-15.1
     */
    private const array HEURISTICALLY_CACHEABLE = [

        self::OK,
        self::NON_AUTHORITATIVE_INFORMATION,
        self::NO_CONTENT,
        self::PARTIAL_CONTENT,
        self::MULTIPLE_CHOICES,
        self::MOVED_PERMANENTLY,
        self::PERMANENT_REDIRECT,
        self::NOT_FOUND,
        self::METHOD_NOT_ALLOWED,
        self::GONE,
        self::REQUEST_URI_TOO_LONG,
        self::NOT_IMPLEMENTED,

    ];

    /**
     * @var list<Status>
     */
    private static array $instances = [];

    /**
     * Returns the status for the specified code.
     *
     * Instances without a custom reason are shared.
     *
     * @param int|Status $status A status code, or a status.
     * @param string|null $reason Reason overriding the standard one, which is used when `null`. When a
     * status is provided, a new instance is returned if a reason is also provided.
     *
     * @throws StatusCodeNotValid if the status code is not valid.
     */
    public static function from(int|self $status, ?string $reason = null): self
    {
        if ($status instanceof self) {
            if ($reason === null) {
                return $status;
            }

            $status = $status->code;
        }

        if ($status < self::MIN || $status > self::MAX) {
            throw new StatusCodeNotValid($status);
        }

        $standard_reason = self::REASONS[$status] ?? '';

        if ($reason === null || $reason === $standard_reason) {
            return self::$instances[$status] ??= new self($status, $standard_reason);
        }

        return new self($status, $reason);
    }

    // The helpers cover the codes used in everyday application logic. Other codes are obtained with
    // `Status::from(Status::GONE)`.

    public static function of_ok(): self
    {
        return self::from(self::OK);
    }

    public static function of_created(): self
    {
        return self::from(self::CREATED);
    }

    public static function of_accepted(): self
    {
        return self::from(self::ACCEPTED);
    }

    public static function of_no_content(): self
    {
        return self::from(self::NO_CONTENT);
    }

    public static function of_moved_permanently(): self
    {
        return self::from(self::MOVED_PERMANENTLY);
    }

    public static function of_found(): self
    {
        return self::from(self::FOUND);
    }

    public static function of_see_other(): self
    {
        return self::from(self::SEE_OTHER);
    }

    public static function of_not_modified(): self
    {
        return self::from(self::NOT_MODIFIED);
    }

    public static function of_temporary_redirect(): self
    {
        return self::from(self::TEMPORARY_REDIRECT);
    }

    public static function of_permanent_redirect(): self
    {
        return self::from(self::PERMANENT_REDIRECT);
    }

    public static function of_bad_request(): self
    {
        return self::from(self::BAD_REQUEST);
    }

    public static function of_unauthorized(): self
    {
        return self::from(self::UNAUTHORIZED);
    }

    public static function of_forbidden(): self
    {
        return self::from(self::FORBIDDEN);
    }

    public static function of_not_found(): self
    {
        return self::from(self::NOT_FOUND);
    }

    public static function of_method_not_allowed(): self
    {
        return self::from(self::METHOD_NOT_ALLOWED);
    }

    public static function of_conflict(): self
    {
        return self::from(self::CONFLICT);
    }

    public static function of_unprocessable_entity(): self
    {
        return self::from(self::UNPROCESSABLE_ENTITY);
    }

    public static function of_too_many_requests(): self
    {
        return self::from(self::TOO_MANY_REQUESTS);
    }

    public static function of_internal_server_error(): self
    {
        return self::from(self::INTERNAL_SERVER_ERROR);
    }

    public static function of_bad_gateway(): self
    {
        return self::from(self::BAD_GATEWAY);
    }

    public static function of_service_unavailable(): self
    {
        return self::from(self::SERVICE_UNAVAILABLE);
    }

    public static function of_gateway_timeout(): self
    {
        return self::from(self::GATEWAY_TIMEOUT);
    }

    private function __construct(
        public readonly int $code,
        /**
         * Reason phrase of the status, empty if the code is not a standard one and no reason was provided.
         */
        public readonly string $reason,
    ) {
    }

    /**
     * Whether the status is informational, its code is between 100 and 199.
     */
    public bool $is_informational {
        get => $this->code >= 100 && $this->code < 200;
    }

    /**
     * Whether the status is successful, its code is between 200 and 299.
     */
    public bool $is_successful {
        get => $this->code >= 200 && $this->code < 300;
    }

    /**
     * Whether the status is a redirection, its code is between 300 and 399.
     */
    public bool $is_redirect {
        get => $this->code >= 300 && $this->code < 400;
    }

    /**
     * Whether the status is a client error, its code is between 400 and 499.
     */
    public bool $is_client_error {
        get => $this->code >= 400 && $this->code < 500;
    }

    /**
     * Whether the status is a server error, its code is between 500 and 599.
     */
    public bool $is_server_error {
        get => $this->code >= 500 && $this->code < 600;
    }

    /**
     * Whether the status is an error, client or server.
     */
    public bool $is_error {
        get => $this->code >= 400;
    }

    /**
     * Whether the status is {@see OK}.
     */
    public bool $is_ok {
        get => $this->code === self::OK;
    }

    /**
     * Whether the status is {@see FORBIDDEN}.
     */
    public bool $is_forbidden {
        get => $this->code === self::FORBIDDEN;
    }

    /**
     * Whether the status is {@see NOT_FOUND}.
     */
    public bool $is_not_found {
        get => $this->code === self::NOT_FOUND;
    }

    /**
     * Whether a response with the status cannot have a body.
     *
     * That is the case of informational responses (`1xx`), {@see NO_CONTENT}, and
     * {@see NOT_MODIFIED}.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#section-6.4.1
     */
    public bool $has_no_body {
        get => $this->code < 200
            || $this->code === self::NO_CONTENT
            || $this->code === self::NOT_MODIFIED;
    }

    /**
     * Whether a response with the status is cacheable by default, without explicit freshness
     * information.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9110#section-15.1
     */
    public bool $is_heuristically_cacheable {
        get => in_array($this->code, self::HEURISTICALLY_CACHEABLE, true);
    }

    public function __toString(): string
    {
        return "$this->code $this->reason";
    }
}
