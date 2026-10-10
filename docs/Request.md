# Request

A request is represented by a [Request][] instance. The initial request is usually created from
the `$_SERVER` array, while sub requests are created from arrays of `Request::OPTION_*` options (see [RequestOptions][]).

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





## Safe and idempotent requests

Safe methods are HTTP methods that don't modify resources.
For instance, using `GET` or `HEAD` on a resource URL, should NEVER change the resource.

The `is_safe()` method of the request method ([RequestMethod][]) may be used to check if a request is safe or not.

```php
<?php

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;

Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_GET ])->method->is_safe(); // true
Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_POST ])->method->is_safe(); // false
Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_DELETE ])->method->is_safe(); // false
```

An idempotent HTTP method is an HTTP method that can be called many times without different
outcomes. It would not matter if the method is called only once, or ten times over. The result
should be the same.

The `is_idempotent()` method may be used to check if a request is idempotent or not.

```php
<?php

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\RequestMethod;

Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_GET ])->method->is_idempotent(); // true
Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_POST ])->method->is_idempotent(); // false
Request::from([ Request::OPTION_METHOD => RequestMethod::METHOD_DELETE ])->method->is_idempotent(); // true
```





## A request with changed properties

Requests are for the most part immutable, the `with()` method creates an instance copy with changed
properties.

```php
<?php

namespace ICanBoogie\HTTP;

$request = Request::from($_SERVER)->with([

    Request::OPTION_METHOD => RequestMethod::METHOD_HEAD,
    Request::OPTION_IS_XHR => true

]);
```




## Request parameters

Whether they're sent as part of the query string, the POST body, or the path info, parameters sent
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





## Request files

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

[File][] represents uploaded files, and _pretend_ uploaded files, with a single API.
The `is_uploaded` property helps you set them apart.

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

The method also handles sets, and returns `true` if there is any match:

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





## Request context

Because requests may be nested, the request context offers a safe place where you can store the
state of your application that is relative to a request. For instance, the context can store the
relative site, page, route, dispatcher…

The following example demonstrates how to store a value in a request context:

```php
<?php

namespace ICanBoogie\HTTP;

/** @var Request $request */
/** @var \ICanBoogie\Routing\Route $route */

$request->context->add($route);

// …

$route = $request->context->find(Route::class);
# or, if the value is required
$route = $request->context->get(Route::class);
```





## Obtaining a response

A response is the result of a [Responder][]'s `respond()` method. An exception is thrown when a
response can't be provided; for example, [NotFound][].

```php
<?php

namespace ICanBoogie\HTTP;

/* @var $request Request */
/* @var ResponderProvider $responder_provider */

// The Responder Provider matches a request with a Responder
$responder = $responder_provider->responder_for_request($request);

// The Responder responds to the request with a Response, it might also throw an exception.
$response = $responder->respond($request);

// The response is sent to the client.
$response();
```



[File]:                          ../lib/File.php
[FileList]:                      ../lib/FileList.php
[NotFound]:                      ../lib/NotFound.php
[RequestMethod]:                 ../lib/RequestMethod.php
[RequestOptions]:                ../lib/RequestOptions.php
[Request]:                       ../lib/Request.php
[Responder]:                     ../lib/Responder.php
