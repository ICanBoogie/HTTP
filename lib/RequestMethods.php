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
 * HTTP request methods.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
 */
interface RequestMethods
{
	const METHOD_ANY = 'ANY';
	const METHOD_CONNECT = 'CONNECT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_TRACE = 'TRACE';
}
