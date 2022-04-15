# Headers

HTTP headers are represented by a [Headers][] instance. They are used by requests and responses, and may be used to
create the headers string of the `mail()` command as well.

A [Headers][] instance can be used as an array but getters and setters are recommended for commonly used headers:

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers['Cache-Control'] = 'public, max-age=3600, no-transform';
$headers->cache_control = 'public, max-age=3600, no-transform';

echo $headers->cache_control->max_age;
```

Some headers have extended implementation to help to manipulate their data:

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->cache_control->cacheable = 'public';
$headers->cache_control->max_age = 3600;
$headers->cache_control->no_transform = true;
```

Assigning `null` to a header removes that header:

```php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->location = '/to/the/moon';
$headers->location = null; // Removes the `Location` header
```

## Cache-Control

The `Cache-Control` header is represented by a [CacheControl][] instance. Directives can be set at
once using a plain string, or individually using the properties of the [CacheControl][] instance.
Directives of the [rfc2616](http://www.w3.org/Protocols/rfc2616/rfc2616.html) are supported.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers['Cache-Control'] = 'public, max-age=3600, no-transform';
$headers->cache_control = 'public, max-age=3600, no-transform';

echo $headers->cache_control; // public, max-age=3600, no-transform
echo $headers->cache_control->cacheable; // public
echo $headers->cache_control->max_age; // 3600
echo $headers->cache_control->no_transform; // true

$headers->cache_control->no_transform = false;
$headers->cache_control->max_age = 7200;

echo $headers->cache_control; // public, max-age=7200
```

## Content-Type

The `Content-Type` header is represented by a [ContentType][] instance.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers['Content-Type'] = 'text/html; charset=utf-8';
$headers->content_type = 'text/html; charset=utf-8';

echo $headers->content_type->type;    // text/html
echo $headers->content_type->charset; // utf-8

$headers->content_type->type = 'application/xml';

echo $headers->content_type; // application/xml; charset=utf-8
```

## Content-Disposition

The `Content-Disposition` header is represented by a [ContentDisposition][] instance. UTF-8 file names are supported.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers['Content-Disposition'] = 'attachment; filename="été.jpg"';
$headers->content_disposition = 'attachment; filename="été.jpg"';

echo $headers->content_disposition->type;     // attachment
echo $headers->content_disposition->filename; // été.jpg

echo $headers->content_disposition; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg
```

## Content-Length

A pair getter/setter is offered to support `Content-Length`, for type safety.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->content_length = 123;
```

## ETag

A pair getter/setter is offered to support the `ETag` header, for type safety.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->etag = "ABC123";
```

## Location

A pair getter/setter is offered to support the `Location` header, for type safety.

**Note:** An exception is thrown when attempting to assign a blank string e.g. `""`, because redirecting to an empty
location is impossible.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->location = "/to/the/moon";
$headers->location = ""; // Throws an exception
```

## Retry-After

A pair getter/setter is offered to support the `Retry-After` header, for type safety.

Both seconds and date notation are supported.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->retry_after = 123;
$headers->retry_after = '+1 hour';
```

## Date, Expires, If-Modified-Since, If-Unmodified-Since, and Last-Modified

All date related headers are represented by a [Date][] instance, and can be specified as Unix timestamp, strings
or `DateTimeInterface` instances.

```php
<?php

use ICanBoogie\HTTP\Headers;

$headers = new Headers();
$headers->date = 'now';
$headers->expires = '+1 hour';
$headers->if_modified_since = '-1 hour';
$headers->if_unmodified_since = '-1 hour';
$headers->last_modified = '2022-01-01';
```

[Headers]:            ../lib/Headers.php
[CacheControl]:       ../lib/Headers/CacheControl.php
[ContentDisposition]: ../lib/Headers/ContentDisposition.php
[ContentType]:        ../lib/Headers/ContentType.php
[Date]:               ../lib/Headers/Date.php
