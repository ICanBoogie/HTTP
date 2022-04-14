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

use function in_array;
use function strtoupper;

/**
 * HTTP request methods.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
 */
enum RequestMethod: string
{
    case METHOD_ANY = 'ANY';
    case METHOD_CONNECT = 'CONNECT';
    case METHOD_DELETE = 'DELETE';
    case METHOD_GET = 'GET';
    case METHOD_HEAD = 'HEAD';
    case METHOD_OPTIONS = 'OPTIONS';
    case METHOD_POST = 'POST';
    case METHOD_PUT = 'PUT';
    case METHOD_PATCH = 'PATCH';
    case METHOD_TRACE = 'TRACE';

    public static function from_mixed(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::from(strtoupper($value));
    }

    /**
     * Whether the method is `CONNECT`.
     */
    public function is_connect(): bool
    {
        return $this === self::METHOD_CONNECT;
    }

    /**
     * Whether the method is `DELETE`.
     */
    public function is_delete(): bool
    {
        return $this === self::METHOD_DELETE;
    }

    /**
     * Whether the method is `GET`.
     */
    public function is_get(): bool
    {
        return $this == self::METHOD_GET;
    }

    /**
     * Whether the method is `HEAD`.
     */
    public function is_head(): bool
    {
        return $this == self::METHOD_HEAD;
    }

    /**
     * Whether the method is `OPTIONS`.
     */
    public function is_options(): bool
    {
        return $this === self::METHOD_OPTIONS;
    }

    /**
     * Whether the method is `PATCH`.
     */
    public function is_patch(): bool
    {
        return $this === self::METHOD_PATCH;
    }

    /**
     * Whether the method is `POST`.
     */
    public function is_post(): bool
    {
        return $this === self::METHOD_POST;
    }

    /**
     * Whether the method is `PUT`.
     */
    public function is_put(): bool
    {
        return $this === self::METHOD_PUT;
    }

    /**
     * Whether the method is `TRACE`.
     */
    public function is_trace(): bool
    {
        return $this === self::METHOD_TRACE;
    }

    /**
     * Whether the method is idempotent.
     *
     * @see https://developer.mozilla.org/en-US/docs/Glossary/Idempotent
     */
    public function is_idempotent(): bool
    {
        return !in_array($this, [

            self::METHOD_CONNECT,
            self::METHOD_PATCH,
            self::METHOD_POST,

        ]);
    }

    /**
     * Whether the method is safe.
     *
     * @see https://developer.mozilla.org/en-US/docs/Glossary/Safe/HTTP
     */
    public function is_safe(): bool
    {
        return in_array($this, [

            self::METHOD_OPTIONS,
            self::METHOD_GET,
            self::METHOD_HEAD,
            self::METHOD_TRACE,

        ]);
    }
}
