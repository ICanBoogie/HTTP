# CHANGELOG

## v7.0.0

### New Requirements

PHP 8.4+

### New features

#### Request

- Added `Request::from_server()` to create the initial request from `$_SERVER` or a copy of it.
- Added `Request::$body_stream`, a `SplFileObject` of the request body, to read JSON, XML, or any
  other payload. `Request::from_server()` sets it to `php://input`, other requests have none unless
  they are created with `Request::OPTION_BODY_STREAM`, which tests can use to provide their own stream.
  `Request::from_server()` does not decode the body, whatever its content type; that's up to the
  application. In particular, `Request::$request_params` stays empty for an `application/json`
  request until the application assigns the decoded data.
- Added `Request::client_ip()`, which returns the client IP from `X-Forwarded-For` when the request
  comes from a trusted proxy.

#### Response

- Added `ResponseSender` and `SimpleResponseSender`. A response no longer sends itself, a sender
  does. `SimpleResponseSender` uses `header()` and the output. The sender only sends a `FinalResponse`:
  `$sender->send($response->finalize($request))`. `finalize()` omits the body of responses to
  `HEAD` requests.
- Added `FinalResponse`, and `Response::finalize(?Request)`, which resolves the status, headers
  and body as they are sent, without modifying the response.

#### Headers

- Added `Headers::from_server()` to create headers from `$_SERVER` or a copy of it.
- Added `AuthenticationFailed` exception.

#### Status

- Added `Status::from(int|Status, ?string $reason = null)`, which returns shared instances, and the
  `Status::of_*()` helpers for the statuses used in everyday application logic (`of_ok()`,
  `of_created()`, `of_not_found()`, …).
- Added `Status::$is_error`, `Status::$has_no_body`, and `Status::$is_heuristically_cacheable`.
- `Status` has a constant and a reason phrase for every standard status code, including `308`, `422`,
  and `429`, for which instantiating a status used to trigger an undefined array key warning.

#### Recovery

- Since `icanboogie/event` supports attaching listeners to interfaces, a `RecoverEvent` listener can
  be attached to an interface implemented by the exception, such as `Throwable`, to recover every
  exception.

### Deprecated Features

None

### Backward Incompatible Changes

#### Request

- `Request::from($_SERVER)` throws a `BadMethodCallException`, use `Request::from_server()`, which also accepts an explicit server array.
- `Request::$ip` no longer reads `X-Forwarded-For`, and neither does `Request::$is_local`. Any
  client could spoof them, for example to make `is_local` return `true`. Use
  `Request::client_ip()` with a list of trusted proxies instead.
- `Request::$headers` is now `private(set)` instead of `readonly`, so that `with()` can replace it.
- `Request::$params` is a read-only property computed on read, instead of a snapshot taken when the
  request was created. `Request::$request_params` is now typed `array`.
- `Request::$cookie` is typed `?array`.
- `File` has a public constructor with named parameters (`new File(pathname: ...)`). `File::from()` still accepts
  an array of properties keyed by the `FileOptions` constants, such as an entry of `$_FILES`.

#### Response

- `Response::finalize(Headers &$headers, &$body)`, `Response::send_headers()`, and
  `Response::send_body()` are replaced by `Response::finalize(?Request): FinalResponse` and the
  `ResponseSender` implementations. Subclasses overriding them must be updated.
  `Response::__invoke()` still sends the response using a `SimpleResponseSender`, but it is
  deprecated.
- A closure used as response body receives the `FinalResponse` instead of the `Response`.
- Exception thrown during Response streaming is no longer captured: it propagates to the caller of
  `$response()`. The headers have already been sent at that point, so a recovery decorator cannot
  replace the response. Handle errors inside the streaming closure.

#### FileResponse

- `FileResponse` no longer takes a `Request`: the constructor is
  `new FileResponse($file, etag:, expires:, filename:, mime:, headers:)`. The `OPTION_ETAG`,
  `OPTION_EXPIRES`, `OPTION_FILENAME` and `OPTION_MIME` constants and the options array are replaced
  by these named parameters, and `filename: true` uses the name of the file. Its status and headers are resolved by
  `finalize($request)`, which doesn't modify the response any more. `FileResponse::$is_modified`
  and `FileResponse::$range` are replaced by `is_modified_for($request)` and `range_for($request)`,
  and `send_file()` takes the length and offset to send.
- `FileResponse` defaults to `Cache-Control: private` instead of `public`, and no longer overwrites
  a `Cache-Control` defined by the caller. Define `Cache-Control: public` explicitly for files that
  may be stored by shared caches.
- The default `ETag` of `FileResponse` is derived from the modification time and the size of the
  file, and is quoted. It used to be an unquoted SHA-384 of the content, computed for every
  response.
- `FileResponse` returns `304 Not Modified` only for `GET` and `HEAD` requests. For other methods,
  a matching `If-None-Match` results in `412 Precondition Failed`, which used to be `200`.
- `FileResponse` quotes the value of `etag` when it isn't quoted already, and throws
  `InvalidArgumentException` for an unquoted value containing a double quote.
- `RequestRange` follows RFC 9110: a last position beyond the end is clamped instead of making the
  range unsatisfiable, a suffix larger than the file selects the whole file, and a range whose last
  position is before its first (`bytes=999-500`) is ignored instead of resulting in a 416.
  `RequestRange::$max_length` is no longer `-1`.

#### Status

- `ResponseStatus` is removed, and `Response` no longer implements it. Use the `Status` constants:
  `ResponseStatus::STATUS_NOT_FOUND` and `Response::STATUS_NOT_FOUND` become `Status::NOT_FOUND`.
  `STATUS_I_M_A_TEAPOT` is `Status::IM_A_TEAPOT`, `STATUS_RECONDITION_REQUIRED` is
  `Status::PRECONDITION_REQUIRED`, and `STATUS_UPDATE_REQUIRED` is `Status::UPGRADE_REQUIRED`.
  `Status::CONTINUE_` is `Status::CONTINUE`, and `Status::USE_PROXY` is removed.
- `Status` is final and immutable, and its constructor is private. Use `Status::from()`:
  `new Status(404)` becomes `Status::from(404)`, and `new Status(499, "Reason")` becomes
  `Status::from(499, "Reason")`. `Status::from()` no longer accepts arrays and strings such as
  `"200 OK"`. The `code` and `message` properties can no longer be set, and a `Response` no longer
  clones its status.
- `Status::$message` is renamed `Status::$reason`, and `Status::$is_valid` is removed, a status is always valid.
- `Status::$is_empty` is replaced by `Status::$has_no_body`, which is true for `1xx`, `204`, and
  `304`, but no longer for `201`. `Response::finalize()` uses it.
- `Status::$is_cacheable` is replaced by `Status::$is_heuristically_cacheable`, which follows RFC 9110
  section 15.1: `204`, `206`, `308`, `405`, `414`, and `501` are now cacheable. `Response::$is_cacheable`
  is affected.
- Statuses are compared by value (`==`), the reason included.

#### Headers

- The `Headers` constructor no longer detects `$_SERVER` arrays by their `REQUEST_URI` key, nor
  filters and normalizes `HTTP_*` keys. Use `Headers::from_server()`.
- `Headers` no longer strips the `;length=xxx` suffix that Internet Explorer 9 appended to
  `If-Modified-Since`.
- `Headers::__invoke()` and `Headers::send_header()` are removed. Use `Headers::fields()`.
- `Headers::getIterator()` returns a `Generator`. `Headers::$content_type` is always a
  `ContentType`.
- `Headers` field names are case-insensitive: `content-type` and `Content-Type` are the same field.
  Names defined by the `Headers::HEADER_*` constants are stored and sent with the spelling of the
  constant, other names keep the spelling they were first given.
- `Headers` throws `InvalidArgumentException` for a field name that is not a valid HTTP token, and
  for a value containing NUL, CR or LF, which prevents header injection. The check happens when
  the field is set, including for `Content-Type`, `Content-Disposition` and `Cache-Control`, and when a
  value is assigned to a `Header` or a `HeaderParameter`. `Headers::fields()` checks again as a
  last resort, since `CacheControl::$extensions` is a public array.
- `Header` no longer uses `__get()`, `__set()` and `__unset()`, nor the `VALUE_ALIAS` constant.
  `ContentType` and `ContentDisposition` declare typed properties (`type`, `charset`, `filename`)
  instead, and child classes declare their parameters with the `PARAMETERS` constant instead of
  populating `$parameters` before calling the parent constructor. Parameters can no longer be
  `unset()` through their property, set them to `null`. `HeaderParameter` instances are still
  available through array access. `Header::$value` is now `?string`.
- `HeaderParameter::from()` throws `InvalidArgumentException` on a malformed parameter. Extended
  values (`filename*=`) are decoded with `rawurldecode()`, so `+` is no longer turned into a space.

### Other changes

#### Request

- `Request::$uri` no longer falls back to `$_SERVER['REQUEST_URI']`; `Request::$port` reads
  `SERVER_PORT` before `REQUEST_PORT` and defaults to 80; `Request::$script_name` and
  `Request::$path` no longer fail when the environment lacks the key. A `POST` request can only
  emulate `PUT`, `PATCH` and `DELETE` with `_method`.
- `Request::with()` supports `OPTION_HEADERS`; it used to throw an error.
- Request headers created from `$_SERVER` now include `Content-Type` and `Content-Length`, which CGI
  exposes as `CONTENT_TYPE` and `CONTENT_LENGTH`.

#### Response

- `Response` discards the body for the statuses that cannot have one (`1xx`, `204`, `304`).
  `Response::$ttl` reads `s-maxage` before `max-age`, so that setting then reading it round-trips,
  and `Response::$version` only accepts `1.0` and `1.1`.

#### FileResponse

- `FileResponse` throws `InvalidArgumentException` for an unsupported option, and a
  `RuntimeException` if the file cannot be opened. `FileResponse::$modified_time` is an `int`.
  `ext-fileinfo` is suggested.
- `FileResponse` answers with `304 Not Modified` when `If-None-Match` matches, even without
  `If-Modified-Since`. `If-None-Match` supports lists, weak tags, and `*`.
- A `416 Range Not Satisfiable` response from `FileResponse` has `Content-Range: bytes */<size>`
  and `Content-Length: 0`.
- `bytes=0-0` is a satisfiable range.
- `If-Range` supports dates, compared to the modification time of the file.

#### Headers

- `CacheControl` supports `immutable`, `stale-while-revalidate` and `stale-if-error`, renders
  its `extensions`, and keys them by their original name. `CacheControl::$max_stale` is `?int`.
- `Headers::$content_length`, `$etag`, `$location` and `$retry_after` return the type they declare.
- `Header::parse()` no longer splits quoted strings on `;`, such as `filename="a;b.txt"`, and
  ignores malformed parameters instead of failing.

#### Project

- `declare(strict_types=1)` is enabled in `lib/`, and PHPStan runs at level 6.
- Documentation links point to RFC 9110, 9111, 8187 and 6266 instead of RFC 2616.
- Documented that `Headers` is single-valued and that cookies are not implemented.
- Moved README doc to docs/



## v6.0.0

### New requirements

- PHP 8.2+

### Backward incompatible changes

The interface `RequestMethods` is replaced with the enum `RequestMethod`. `is_*` method related to
HTTP methods have been moved from `Request` to the enum.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request $request */

$request->is_delete;
$request->is_safe;
```

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request $request */

$request->method->is_delete();
$request->method->is_safe();
```

`Context` no longer implements `ArrayAccess` and no longer extends `Prototype`.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request\Context $context */

$context['request']
$context['something'] = $something;
$something_back = $context['something'];
```

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request\Context $context */

$context->request;
$context->add($something);
$something_back = $context->get($something::class);
```

Headers with extended support, such as `Cache-Control`, can now be accessed with special properties. Accessor related to
these headers have been moved from `Request` and `Response` to `Headers`.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request $request */
/* @var Response $response */
/* @var Headers $headers */

$request->cache_control;
$response->cache_control;
$response->content_length;
$response->content_type;
$response->date;
$response->etag;
$response->last_modified;
$response->location;
$headers['Cache-Control'];
$headers['Content-Disposition'];
```

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Request $request */
/* @var Response $response */
/* @var Headers $headers */

$request->headers->cache_control;
$response->headers->cache_control;
$response->headers->content_length;
$response->headers->content_type;
$response->headers->date;
$response->headers->etag;
$response->headers->last_modified;
$response->headers->location;
$headers['Cache-Control']; $headers->cache_control;
$headers['Content-Disposition']; $headers->content_disposition;
```

Dropped everything related to Dispatchers in favor of Responder providers and Responders.
That includes helper functions such as `dispatch` and `get_initial_request`.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var RequestDispatcher $dispatcher */
/* @var Request $request */

$response = $dispatcher->dispatch($request);
```

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Responder $responder */
/* @var Request $request */

$response = $responder->respond($request);
```

Dropped all `RequestOptions::OPTION_IS_` related to HTTP methods,
use `RequestOptions::OPTION_METHOD` instead:

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from([ RequestOptions::OPTION_IS_DELETE => true ]);
```

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from([ RequestOptions::OPTION_METHOD => RequestMethod::METHOD_DELETE ]);
```

Added `WithRecovery` to replace previous recovery feature. Renamed `RescueEvent` as `RecoverEvent`.



### Other changes

The package `icanboogie/datetime` is only required for development.
