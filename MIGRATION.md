# Migration

## v5.x to v6.x

The interface `RequestMethods` is replaced with the enum `RequestMethod`. `is_*` method related to HTTP methods have
been moved from `Request` to the enum.

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
or
Response::STATUS_OK;
```

Dropped everything related to Dispatchers in favor of Responder providers and Responders.

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

Dropped all `RequestOptions::OPTION_IS_` related to HTTP methods, use `RequestOptions::OPTION_METHOD` instead:

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
