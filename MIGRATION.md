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

Headers with extended support, such as `Cache-Headers`, can now be accessed with special properties. Accessor related to
these headers have been moved from `Request` and `Response` to `Headers`.

```php
<?php

/* @var \ICanBoogie\HTTP\Request $request */
/* @var \ICanBoogie\HTTP\Response $response */

$request->cache_control;
$response->cache_control;
```
```php
<?php

/* @var \ICanBoogie\HTTP\Request $request */
/* @var \ICanBoogie\HTTP\Response $response */

$request->headers->cache_control;
$response->headers->cache_control;
```
