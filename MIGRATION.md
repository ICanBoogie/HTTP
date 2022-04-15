# Migration

## v5.x to v6.x

The interface `RequestOptions` is replaced with the enum `RequestOption`.

```php
$request->is_delete;
$request->is_safe;
```

```php
$request->method->is_delete();
$request->method->is_safe();
```

`Context` no longer implements `ArrayAccess` and no longer extends `Prototype`.

```php
$context['request']
$context->request;
```regexp
$context->get(Request::class);
```

Headers with extended support, such as `Cache-Control`, can now be accessed with special properties. Accessor related to
these headers have been moved from `Request` and `Response` to `Headers`.

```php
<?php

/* @var \ICanBoogie\HTTP\Request $request */
/* @var \ICanBoogie\HTTP\Response $response */
/* @var \ICanBoogie\HTTP\Headers $headers */

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

/* @var \ICanBoogie\HTTP\Request $request */
/* @var \ICanBoogie\HTTP\Response $response */
/* @var \ICanBoogie\HTTP\Headers $headers */

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

ICanBoogie\HTTP\Status::OK;
```

```php
<?php

ICanBoogie\HTTP\ResponseStatus::STATUS_OK;
or
ICanBoogie\HTTP\Response::STATUS_OK;
```
