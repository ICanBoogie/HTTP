# CHANGELOG

## v7.0.0

### New Requirements

PHP 8.4+

### New features

- Added `AuthenticationFailed` exception.
- Added `Request::client_ip()`, which returns the client IP from `X-Forwarded-For` when the request
  comes from a trusted proxy.

### Deprecated Features

None

### Backward Incompatible Changes

- Exception thrown during Response streaming is no longer captured: it propagates to the caller of
  `$response()`. The headers have already been sent at that point, so a recovery decorator cannot
  replace the response. Handle errors inside the streaming closure.
- `Request::$ip` no longer reads `X-Forwarded-For`, and neither does `Request::$is_local`. Any
  client could spoof them, for example to make `is_local` return `true`. Use
  `Request::client_ip()` with a list of trusted proxies instead.
- `Request::$headers` is now `private(set)` instead of `readonly`, so that `with()` can replace it.
- `FileResponse` defaults to `Cache-Control: private` instead of `public`, and no longer overwrites
  a `Cache-Control` defined by the caller. Define `Cache-Control: public` explicitly for files that
  may be stored by shared caches.
- The default `ETag` of `FileResponse` is derived from the modification time and the size of the
  file, and is quoted. It used to be an unquoted SHA-384 of the content, computed for every
  response.
- `FileResponse` returns `304 Not Modified` only for `GET` and `HEAD` requests. For other methods,
  a matching `If-None-Match` results in `412 Precondition Failed`, which used to be `200`.
- `FileResponse` quotes the value of `OPTION_ETAG` when it isn't quoted already, and throws
  `InvalidArgumentException` for an unquoted value containing a double quote.
- A malformed JSON request body, or one that is not a JSON object or array, throws a `ClientError`
  (400) instead of a `JsonException`. An empty JSON body results in no request parameters.
- `RequestRange` follows RFC 9110: a last position beyond the end is clamped instead of making the
  range unsatisfiable, a suffix larger than the file selects the whole file, and a range whose last
  position is before its first (`bytes=999-500`) is ignored instead of resulting in a 416.
  `RequestRange::$max_length` is no longer `-1`.
- `HeaderParameter::from()` throws `InvalidArgumentException` on a malformed parameter. Extended
  values (`filename*=`) are decoded with `rawurldecode()`, so `+` is no longer turned into a space.
- `Headers` field names are case-insensitive: `content-type` and `Content-Type` are the same field.
  Names defined by the `Headers::HEADER_*` constants are stored and sent with the spelling of the
  constant, other names keep the spelling of the last assignment.
- `Header` no longer uses `__get()`, `__set()` and `__unset()`, nor the `VALUE_ALIAS` constant.
  `ContentType` and `ContentDisposition` declare typed properties (`type`, `charset`, `filename`)
  instead, and child classes declare their parameters with the `PARAMETERS` constant instead of
  populating `$parameters` before calling the parent constructor. Parameters can no longer be
  `unset()` through their property, set them to `null`. `HeaderParameter` instances are still
  available through array access. `Header::$value` is now `?string`.
- `Request::$params` is a read-only property computed on read, instead of a snapshot taken when the
  request was created. `Request::$request_params` is now typed `array`.
- `Request::$cookie` is typed `?array`.
- `Headers::getIterator()` returns a `Generator`. `Headers::$content_type` is always a
  `ContentType`.
- `Headers` throws `InvalidArgumentException` for a field name that is not a valid HTTP token, and
  for a value containing NUL, CR or LF, which prevents header injection. Values of `Header`
  objects are checked when the headers are serialized or sent.

### Other changes

- `Response` discards the body for the statuses that cannot have one (`1xx`, `204`, `304`).
  `Response::$ttl` reads `s-maxage` before `max-age`, so that setting then reading it round-trips,
  and `Response::$version` only accepts `1.0` and `1.1`.
- `FileResponse` throws `InvalidArgumentException` for an unsupported option, and a
  `RuntimeException` if the file cannot be opened. `FileResponse::$modified_time` is an `int`.
  `ext-fileinfo` is suggested.
- `Request::$uri` no longer falls back to `$_SERVER['REQUEST_URI']`; `Request::$port` reads
  `SERVER_PORT` before `REQUEST_PORT` and defaults to 80; `Request::$script_name` and
  `Request::$path` no longer fail when the environment lacks the key. A `POST` request can only
  emulate `PUT`, `PATCH` and `DELETE` with `_method`.
- `CacheControl` supports `immutable`, `stale-while-revalidate` and `stale-if-error`, renders
  its `extensions`, and keys them by their original name. `CacheControl::$max_stale` is `?int`.
- `Headers::$content_length`, `$etag`, `$location` and `$retry_after` return the type they declare.
- `declare(strict_types=1)` is enabled in `lib/`, and PHPStan runs at level 6.
- Documentation links point to RFC 9110, 9111, 8187 and 6266 instead of RFC 2616.
- Documented that `Headers` is single-valued and that cookies are not implemented.
- Moved README doc to docs/
- Request headers created from `$_SERVER` now include `Content-Type` and `Content-Length`, which CGI
  exposes as `CONTENT_TYPE` and `CONTENT_LENGTH`. As a consequence, JSON request bodies are now
  decoded.
- `Request::with()` supports `OPTION_HEADERS`; it used to throw an error.
- `FileResponse` answers with `304 Not Modified` when `If-None-Match` matches, even without
  `If-Modified-Since`. `If-None-Match` supports lists, weak tags, and `*`.
- A `416 Range Not Satisfiable` response from `FileResponse` has `Content-Range: bytes */<size>`
  and `Content-Length: 0`.
- `bytes=0-0` is a satisfiable range.
- `If-Range` supports dates, compared to the modification time of the file.
- `Header::parse()` no longer splits quoted strings on `;`, such as `filename="a;b.txt"`, and
  ignores malformed parameters instead of failing.



## v4.x to v6.0

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

`Status` constants are deprecated in favor of `ResponseStatus` equivalent:

```php
<?php

namespace ICanBoogie\HTTP;

Status::OK;
```

```php
<?php

namespace ICanBoogie\HTTP;

ResponseStatus::STATUS_OK;
# or
Response::STATUS_OK;
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
