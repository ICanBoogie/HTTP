# Response

The response to a request is represented by a [Response][] instance. The response body can
either be `null`, a string (or `Stringable`), or a `Closure`.

> **Note:** Contrary to [Request][] instances, [Response][] instances are completely mutable.

```php
<?php

namespace ICanBoogie\HTTP;

$response = new Response('<!DOCTYPE html><html><body><h1>Hello world!</h1></body></html>', Response::STATUS_OK, [

    Headers::HEADER_CONTENT_TYPE => 'text/html',
    Headers::HEADER_CACHE_CONTROL => 'public, max-age=3600',

]);
```

The header and body are sent by invoking the response:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $response Response */

$response();
```





## Response status

The response status is represented by a [Status][] instance. It may be assigned as an HTTP response
code such as `200`, or as a [Status][] instance. [Status::from()][Status] creates an instance from
a code, an array such as `[ 200, "OK" ]`, or a string such as `"200 OK"`.

```php
<?php

namespace ICanBoogie\HTTP;

$response = new Response;

echo $response->status;               // 200 OK
echo $response->status->code;         // 200
echo $response->status->message;      // OK
$response->status->is_valid;          // true

$response->status = Response::STATUS_NOT_FOUND;
echo $response->status->code;         // 404
echo $response->status->message;      // Not Found
$response->status->is_valid;          // true
$response->status->is_client_error;   // true
$response->status->is_not_found;      // true
```





## Streaming the response body

When a large response body needs to be streamed, it is recommended to use a closure as a response
body instead of a huge string that would consume a lot of memory.

```php
<?php

namespace ICanBoogie\HTTP;

$records = $app->models->order('created_at DESC');

$output = function() use ($records) {

    $out = fopen('php://output', 'w');

    foreach ($records as $record)
    {
        fputcsv($out, [ $record->title, $record->created_at ]);
    }

    fclose($out);

};

$response = new Response($output, Response::STATUS_OK, [ 'Content-Type' => 'text/csv' ]);
```





## About the `Content-Length` header field

The `Content-Length` header field is not added automatically, even when it is computable (for
instance when the body is a string or an instance implementing `__toString()`). It has to be defined
when required. This was decided to prevent a bug with Apache+FastCGI+DEFLATE where the `Content-Length` field
wasn't adjusted although the body was compressed. Also, in most cases it is not such a good idea
to define that field for generated content because it prevents the response from being sent as
[compressed chunks](http://en.wikipedia.org/wiki/Chunked_transfer_encoding).





## Redirect response

A redirect response may be created using a [RedirectResponse][] instance.

```php
<?php

namespace ICanBoogie\HTTP;

$response = new RedirectResponse('/to/redirect/location');
$response->status->code;        // 302
$response->status->is_redirect; // true
```





## Delivering a file

A file may be delivered using a [FileResponse][] instance. Cache control and _range_ requests
are handled automatically; you only have to provide the pathname of the file, or a `SplFileInfo`
instance, and a request.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$response = new FileResponse("/absolute/path/to/my/file", $request);
$response();
```

The `OPTION_FILENAME` option may be used to force downloading. Of course, utf-8 strings are
supported:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$response = new FileResponse("/absolute/path/to/my/file", $request, [

    FileResponse::OPTION_FILENAME => "Vidéo d'un été à la mer.mp4"

]);

$response();
```

The following options are also available:

- `OPTION_ETAG`: Specifies the `ETag` header field of the response. If it is not defined, the
[SHA-384][] of the file is used instead.

- `OPTION_EXPIRES`: Specifies the expiration date as a `DateTime` instance or a relative date
such as `+3 month`, which maps to the `Expires` header field. The `max-age` directive of the
`Cache-Control` header field is computed from the current time. If it is not defined
`DEFAULT_EXPIRES` is used instead ("+1 month").

- `OPTION_MIME`: Specifies the MIME of the file, which maps to the `Content-Type` header field.
If it is not defined the MIME is guessed using `finfo::file()`.

The following properties are available:

- `modified_time`: Returns the last modified timestamp of the file.

- `is_modified`: Whether the file has been modified since the last response. The value is computed
using the request header fields `If-None-Match` and `If-Modified-Since`, and the properties
`modified_time` and `etag`.



[FileResponse]:                  ../lib/FileResponse.php
[RedirectResponse]:              ../lib/RedirectResponse.php
[Request]:                       ../lib/Request.php
[Response]:                      ../lib/Response.php
[Status]:                        ../lib/Status.php
[SHA-384]:                       https://en.wikipedia.org/wiki/SHA-2
