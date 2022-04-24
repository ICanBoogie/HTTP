# HTTP

[![Release](https://img.shields.io/packagist/v/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)
[![Code Quality](https://img.shields.io/scrutinizer/g/icanboogie/http.svg)](https://scrutinizer-ci.com/g/ICanBoogie/HTTP)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/HTTP.svg)](https://coveralls.io/r/ICanBoogie/HTTP)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)

The **icanboogie/http** package provides a foundation to handle HTTP requests, with representations
for requests, request files, responses, and headers. The package also lay the foundation of

The following example is an overview of a request processing:

```php
<?php

namespace ICanBoogie\HTTP;

// The request is usually created from the $_SERVER super global.
$request = Request::from($_SERVER);

/* @var ResponderProvider $responder_provider */

// The Responder Provider matches a request with a Responder
$responder = $responder_provider->responder_for_request($request);

// The Responder responds to the request with a Response, it might also throw an exception.
$response = $responder->respond($request);

// The response is sent to the client.
$response();
```



#### Installation

```bash
composer require icanboogie/http
```



## Request

A request is represented by a [Request][] instance. The initial request is usually created from
the `$_SERVER` array, while sub requests are created from arrays of `Request::OPTION_*` or
`RequestOptions::OPTION_*` options.

```php
<?php

namespace ICanBoogie\HTTP;

$initial_request = Request::from($_SERVER);

# a custom request in the same environment

$request = Request::from('path/to/file.html', $_SERVER);

# a request created from scratch

$request = Request::from([

    Request::OPTION_PATH => 'path/to/file.html',
    Request::OPTION_IS_LOCAL => true, // or OPTION_IP => '::1'
    Request::OPTION_METHOD => RequestMethod::METHOD_POST,
    Request::OPTION_HEADERS => [

        'Cache-Control' => 'no-cache'

    ]

]);
```





### Safe and idempotent requests

Safe methods are HTTP methods that do not modify resources. For instance, using `GET` or `HEAD` on a
resource URL, should NEVER change the resource.

The `is_safe` property may be used to check if a request is safe or not.

```php
<?php

use ICanBoogie\HTTP\Request;

Request::from([ Request::OPTION_METHOD => Request::METHOD_GET ])->is_safe; // true
Request::from([ Request::OPTION_METHOD => Request::METHOD_POST ])->is_safe; // false
Request::from([ Request::OPTION_METHOD => Request::METHOD_DELETE ])->is_safe; // false
```

An idempotent HTTP method is a HTTP method that can be called many times without different outcomes.
It would not matter if the method is called only once, or ten times over. The result should be the
same.

The `is_idempotent` property may be used to check if a request is idempotent or not.

```php
<?php

use ICanBoogie\HTTP\Request;

Request::from([ Request::OPTION_METHOD => Request::METHOD_GET ])->is_idempotent; // true
Request::from([ Request::OPTION_METHOD => Request::METHOD_POST ])->is_idempotent; // false
Request::from([ Request::OPTION_METHOD => Request::METHOD_DELETE ])->is_idempotent; // true
```





### A request with changed properties

Requests are for the most part immutable, the `with()` method creates an instance copy with changed
properties.

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from($_SERVER)->with([

    Request::OPTION_METHOD => RequestMethod::METHOD_HEAD => true,
    Request::OPTION_IS_XHR => true

]);
```




### Request parameters

Whether they are sent as part of the query string, the post body, or the path info, parameters sent
along a request are collected in arrays. The `query_params`, `request_params`, and `path_params`
properties give you access to these parameters.

You can access each type of parameter as follows:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$id = $request->query_params['id'];
$method = $request->request_params['method'];
$info = $request->path_params['info'];
```

All the request parameters are also available through the `params` property, which merges the
_query_, _request_ and _path_ parameters:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$id = $request->params['id'];
$method = $request->params['method'];
$info = $request->params['info'];
```

Used as an array, the [Request][] instance provides these parameters as well, but returns `null`
when a parameter is not defined:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$id = $request['id'];
$method = $request['method'];
$info = $request['info'];

var_dump($request['undefined']); // null
```

Of course, the request is also an iterator:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

foreach ($request as $parameter => $value)
{
    echo "$parameter: $value\n";
}
```





### Request files

Files associated with a request are collected in a [FileList][] instance. The initial request
created with `$_SERVER` obtain its files from `$_FILES`. For custom requests, files are defined
using `OPTION_FILES`.

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from($_SERVER);

# or

$request = Request::from([

    Request::OPTION_FILES => [

        'uploaded' => [ FileOptions::OPTION_PATHNAME => '/path/to/my/example.zip' ]

    ]

]);

#

$files = $request->files;    // instanceof FileList
$file = $files['uploaded'];  // instanceof File
$file = $files['undefined']; // null
```

Uploaded files, and _pretend_ uploaded files, are represented by [File][] instances. The class
tries its best to provide the same API for both. The `is_uploaded` property helps you set
them apart.

The `is_valid` property is a simple way to check if a file is valid. The `move()` method
lets you move the file out of the temporary folder or around the filesystem.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $file File */

echo $file->name;            // example.zip
echo $file->unsuffixed_name; // example
echo $file->extension;       // .zip
echo $file->size;            // 1234
echo $file->type;            // application/zip
echo $file->is_uploaded;     // false

if ($file->is_valid)
{
    $file->move('/path/to/repository/' . $file->name, File::MOVE_OVERWRITE);
}
```

The `match()` method is used to check if a file matches a MIME type, a MIME class, or a file
extension:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $file File */

echo $file->match('application/zip');             // true
echo $file->match('application');                 // true
echo $file->match('.zip');                        // true
echo $file->match('image/png');                   // false
echo $file->match('image');                       // false
echo $file->match('.png');                        // false
```

The method also handles sets, and returns `true` if there's any match:

```php
<?php

echo $file->match([ '.png', 'application/zip' ]); // true
echo $file->match([ '.png', '.zip' ]);            // true
echo $file->match([ 'image/png', '.zip' ]);       // true
echo $file->match([ 'image/png', 'text/plain' ]); // false
```

[File][] instances can be converted into arrays with the `to_array()` method:

```php
<?php

$file->to_array();
/*
[
    'name' => 'example.zip',
    'unsuffixed_name' => 'example',
    'extension' => '.zip',
    'type' => 'application/zip',
    'size' => 1234,
    'pathname' => '/path/to/my/example.zip',
    'error' => null,
    'error_message' => null
]
*/
```





### Request context

Because requests may be nested the request context offers a safe place where you can store the state
of your application that is relative to a request, for instance a request relative site, page,
route, dispatcher… The context may be used as an array, but is also a prototyped instance.

The following example demonstrates how to store a value in a request context:

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from($_SERVER);
$request->context['site'] = $app->models['sites']->one;
```

The following example demonstrates how to use the prototype feature to provide a value when it is
requested from the context:

```php
<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\HTTP\Request\Context;
use ICanBoogie\Prototype;

Prototype::from(Context::class)['lazy_get_site'] = function(Context $context) use ($site_model) {

    return $site_model->resolve_from_request($context->request);

};

$request = Request::from($_SERVER);

$site = $request->context['site'];
# or
$site = $request->context->site;
```





### Obtaining a response

A response is obtained from a request simply by invoking the request, or by invoking one of the
available HTTP methods. The `dispatch()` helper is used to dispatch the request. A [Response][]
instance is returned if the dispatching is successful, a [NotFound][] exception is
thrown otherwise.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$response = $request();

# using the POST method and additional parameters

$response = $request->post([ 'param' => 'value' ]);
```





## Response

The response to a request is represented by a [Response][] instance. The response body can
either be `null`, a string (or `Stringable`), or a `Closure`.

> **Note:** Contrary to [Request][] instances, [Response][] instances or completely mutable.

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





### Response status

The response status is represented by a [Status][] instance. It may be defined as a HTTP response
code such as `200`, an array such as `[ 200, "Ok" ]`, or a string such as `"200 Ok"`.

```php
<?php

namespace ICanBoogie\HTTP;

$response = new Response;

echo $response->status;               // 200 Ok
echo $response->status->code;         // 200
echo $response->status->message;      // Ok
$response->status->is_valid;          // true

$response->status = Response::STATUS_NOT_FOUND;
echo $response->status->code;         // 404
echo $response->status->message;      // Not Found
$response->status->is_valid;          // false
$response->status->is_client_error;   // true
$response->status->is_not_found;      // true
```





### Streaming the response body

When a large response body needs to be streamed, it is recommended to use a closure as response
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





### About the `Content-Length` header field

Before v2.3.2 the `Content-Length` header field was added automatically when it was computable,
for instance when the body was a string or an instance implementing `__toString()`.
Starting v2.3.2 this is no longer the case and the header field has to be defined when required.
This was decided to prevent a bug with Apache+FastCGI+DEFLATE where the `Content-Length` field
was not adjusted although the body was compressed. Also, in most cases it's not such a good idea
to define that field for generated content because it prevents the response to be sent as
[compressed chunks](http://en.wikipedia.org/wiki/Chunked_transfer_encoding).





### Redirect response

A redirect response may be created using a [RedirectResponse][] instance.

```php
<?php

namespace ICanBoogie\HTTP;

$response = new RedirectResponse('/to/redirect/location');
$response->status->code;        // 302
$response->status->is_redirect; // true
```





### Delivering a file

A file may be delivered using a [FileResponse][] instance. Cache control and _range_ requests
are handled automatically, you just need to provide the pathname of the file, or a `SplFileInfo`
instance, and a request.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */

$response = new FileResponse("/absolute/path/to/my/file", $request);
$response();
```

The `OPTION_FILENAME` option may be used to force downloading. Of course, utf-8 string are
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

- `OPTION_ETAG`: Specifies the `ETag` header field of the response. If it is not defined the
[SHA-384][] of the file is used instead.

- `OPTION_EXPIRES`: Specifies the expiration date as a `DateTime` instance or a relative date
such as "+3 month", which maps to the `Expires` header field. The `max-age` directive of the
`Cache-Control` header field is computed from the current time. If it is not defined
`DEFAULT_EXPIRES` is used instead ("+1 month").

- `OPTION_MIME`: Specifies the MIME of the file, which maps to the `Content-Type` header field.
If it is not defined the MIME is guessed using `finfo::file()`.

The following properties are available:

- `modified_time`: Returns the last modified timestamp of the file.

- `is_modified`: Whether the file was modified since the last response. The value is computed
using the request header fields `If-None-Match` and `If-Modified-Since`, and the properties
`modified_time` and `etag`.





## Headers

Here's an overview of headers usage, details are available in the [Headers documentation](docs/Headers.md).

```php
<?php

namespace ICanBoogie\HTTP;

$headers = new Headers();

$headers->cache_control = 'public, max-age=3600, no-transform';
$headers->cache_control->no_transform = false;
$headers->cache_control->max_age = 7200;

echo $headers->cache_control; // public, max-age=7200

$headers->content_type = 'text/plain';
$headers->content_type->type = 'application/xml';
$headers->content_type->charset = 'utf-8';

echo $headers->content_type; // application/xml; charset=utf-8

$headers->content_length = 123;

$headers->content_disposition->type = 'attachment';
$headers->content_disposition->filename = 'été.jpg';

echo $headers->content_disposition; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg

$headers->etag = "ABC123";

$headers->date = 'now';
$headers->expires = '+1 hour';
$headers->if_modified_since = '-1 hour';
$headers->if_unmodified_since = '-1 hour';
$headers->last_modified = '2022-01-01';
$headers->retry_after = '+1 hour';
$headers->retry_after = 123;

$headers->location = 'to/the/moon';

$headers['X-My-Header'] = 'Some value';
echo $headers['X-My-Header']; // 'Some value';
```



## Exceptions

The following exceptions are defined by the HTTP package:

* [ClientError][]: thrown when a client error occurs.
    * [NotFound][]: thrown when a resource is not found. For instance, this exception is
    thrown by the dispatcher when it fails to resolve a request into a response.
    * [AuthenticationRequired][]: thrown when the authentication of the client is required. Implements [SecurityError][].
    * [PermissionRequired][]: thrown when the client lacks a required permission. Implements [SecurityError][].
    * [MethodNotAllowed][]: thrown when a HTTP method is not supported.
* [ServerError][]: throw when a server error occurs.
    * [ServiceUnavailable][]: thrown when a server is currently unavailable
    (because it is overloaded or down for maintenance).
* [ForceRedirect][]: thrown when a redirect is absolutely required.
* [StatusCodeNotValid][]: thrown when a HTTP status code is not valid.

Exceptions defined by the package implement the `ICanBoogie\HTTP\Exception` interface.
Using this interface one can easily catch HTTP related exceptions:

```php
<?php

try
{
    // …
}
catch (\ICanBoogie\HTTP\Exception $e)
{
    // HTTP exception types
}
catch (\Exception $e)
{
    // Other exception types
}
```





## Helpers

The following helpers are available:

* [`dispatch()`][]: Dispatches a request using the dispatcher returned by [`get_dispatcher()`][].
* [`get_dispatcher()`][]: Returns the request dispatcher. If no dispatcher provider is defined,
the method defines a new instance of [DispatcherProvider][] as provider and use it to retrieve the
dispatcher.
* [`get_initial_request()`][]: Returns the initial request.

```php
<?php

namespace ICanBoogie\HTTP;

$request = get_initial_request();
$response = dispatch($request);
```





----------



## Continuous Integration

The project is continuously tested by [GitHub actions](https://github.com/ICanBoogie/HTTP/actions).

[![Tests](https://github.com/ICanBoogie/HTTP/workflows/test/badge.svg?branch=master)](https://github.com/ICanBoogie/HTTP/actions?query=workflow%3Atest)
[![Static Analysis](https://github.com/ICanBoogie/HTTP/workflows/static-analysis/badge.svg?branch=master)](https://github.com/ICanBoogie/HTTP/actions?query=workflow%3Astatic-analysis)
[![Code Style](https://github.com/ICanBoogie/HTTP/workflows/code-style/badge.svg?branch=master)](https://github.com/ICanBoogie/HTTP/actions?query=workflow%3Acode-style)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you are expected to uphold this code.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



## Testing

Run `make test-container` to create and log into the test container, then run `make test` to run the
test suite. Alternatively, run `make test-coverage` to run the test suite with test coverage. Open
`build/coverage/index.html` to see the breakdown of the code coverage.



## License

**icanboogie/http** is released under the [BSD-3-Clause](LICENSE).





[AuthenticationRequired]:        lib/AuthenticationRequired.php
[CacheControl]:                  lib/Headers/CacheControl.php
[ClientError]:                   lib/ClientError.php
[ContentType]:                   lib/Headers/ContentType.php
[Headers]:                       lib/Headers.php
[File]:                          lib/File.php
[FileList]:                      lib/FileList.php
[FileResponse]:                  lib/FileResponse.php
[ForceRedirect]:                 lib/ForceRedirect.php
[MethodNotAllowed]:              lib/MethodNotAllowed.php
[NotFound]:                      lib/NotFound.php
[PermissionRequired]:            lib/PermissionRequired.php
[RedirectResponse]:              lib/RedirectResponse.php
[Request]:                       lib/Request.php
[Response]:                      lib/Response.php
[SecurityError]:                 lib/SecurityError.php
[ServerError]:                   lib/ServerError.php
[ServiceUnavailable]:            lib/ServiceUnavailable.php
[StatusCodeNotValid]:            lib/StatusCodeNotValid.php
[Status]:                        lib/Status.php
[`get_initial_request()`]:       helpers.php

[ICanBoogie]:         https://icanboogie.org/
[icanboogie/routing]: https://github.com/ICanBoogie/Routing
[SHA-384]:            https://en.wikipedia.org/wiki/SHA-2
