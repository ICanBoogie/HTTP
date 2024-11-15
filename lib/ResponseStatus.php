<?php

namespace ICanBoogie\HTTP;

/**
 * Possible response status code.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 */
interface ResponseStatus
{
    public const int STATUS_CONTINUE = 100;
    public const int STATUS_SWITCHING_PROTOCOLS = 101;
    public const int STATUS_PROCESSING = 102;
    public const int STATUS_EARLY_HINTS = 103;

    public const int STATUS_OK = 200;
    public const int STATUS_CREATED = 201;
    public const int STATUS_ACCEPTED = 202;
    public const int STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    public const int STATUS_NO_CONTENT = 204;
    public const int STATUS_RESET_CONTENT = 205;
    public const int STATUS_PARTIAL_CONTENT = 206;
    public const int STATUS_MULTI_STATUS = 207;
    public const int STATUS_ALREADY_REPORTED = 208;
    public const int STATUS_IM_USED = 226;

    public const int STATUS_MULTIPLE_CHOICES = 300;
    public const int STATUS_MOVED_PERMANENTLY = 301;
    public const int STATUS_FOUND = 302;
    public const int STATUS_SEE_OTHER = 303;
    public const int STATUS_NOT_MODIFIED = 304;
    public const int STATUS_TEMPORARY_REDIRECT = 307;
    public const int STATUS_PERMANENT_REDIRECT = 308;

    public const int STATUS_BAD_REQUEST = 400;
    public const int STATUS_UNAUTHORIZED = 401;
    public const int STATUS_PAYMENT_REQUIRED = 402;
    public const int STATUS_FORBIDDEN = 403;
    public const int STATUS_NOT_FOUND = 404;
    public const int STATUS_METHOD_NOT_ALLOWED = 405;
    public const int STATUS_NOT_ACCEPTABLE = 406;
    public const int STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int STATUS_REQUEST_TIMEOUT = 408;
    public const int STATUS_CONFLICT = 409;
    public const int STATUS_GONE = 410;
    public const int STATUS_LENGTH_REQUIRED = 411;
    public const int STATUS_PRECONDITION_FAILED = 412;
    public const int STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
    public const int STATUS_REQUEST_URI_TOO_LONG = 414;
    public const int STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    public const int STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const int STATUS_EXPECTATION_FAILED = 417;
    public const int STATUS_I_M_A_TEAPOT = 418;
    public const int STATUS_MISDIRECTED_REQUEST = 421;
    public const int STATUS_UNPROCESSABLE_ENTITY = 422;
    public const int STATUS_LOCKED = 423;
    public const int STATUS_FAILED_DEPENDENCY = 424;
    public const int STATUS_TOO_EARLY = 425;
    public const int STATUS_UPDATE_REQUIRED = 426;
    public const int STATUS_RECONDITION_REQUIRED = 428;
    public const int STATUS_TOO_MANY_REQUESTS = 429;
    public const int STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const int STATUS_UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    public const int STATUS_INTERNAL_SERVER_ERROR = 500;
    public const int STATUS_NOT_IMPLEMENTED = 501;
    public const int STATUS_BAD_GATEWAY = 502;
    public const int STATUS_SERVICE_UNAVAILABLE = 503;
    public const int STATUS_GATEWAY_TIMEOUT = 504;
    public const int STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
    public const int STATUS_VARIANT_ALSO_NEGOTIATES = 506;
    public const int STATUS_INSUFFICIENT_STORAGE = 507;
    public const int STATUS_LOOP_DETECTED = 508;
    public const int STATUS_NOT_EXTENDED = 510;
    public const int STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
}
