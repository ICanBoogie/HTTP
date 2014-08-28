# HTTP [![Build Status](https://secure.travis-ci.org/ICanBoogie/HTTP.svg?branch=2.1)](http://travis-ci.org/ICanBoogie/HTTP)

The HTTP package provides an API to handle HTTP requests.





## Request

A request is represented by a [Request](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Request.html) instance.
The initial request is usually created from the `$_SERVER` array, while sub requests are created
from arrays of properties.

```php
<?php

use ICanBoogie\HTTP\Request;

$initial_request = Request::from($_SERVER);

# a fake request in the same environment

$request = Request::from('path/to/file.html', $_SERVER);

# a request created from scratch

$request = Request::from([

	'path' => 'path/to/file.html',
	'is_local' => true,            // or 'ip' => '::1'
	'is_post' => true,             // or 'method' => Request::METHOD_POST
	'content_length' => 123,
	'headers' => [

		'Cache-Control' => 'no-cache'

	]

]);
```

Requests are handled by a dispatcher, which returns a [Response][]
instance, or throws a `NotFound` exception if the request cannot be satisfied.
Requests are usually dispatched simply by invoking them, or by using one of the HTTP methods
available:

```php
<?php

$response = $request();

# or

$response = $request->post([ 'arg' => 'value' ]);
```




### Request parameters

Parameters sent along the request are collected in arrays, whether they are sent as part of the
query string, the post body or the path info. The `query_params`, `request_params` and
`path_params` give you access to these parameters.

You can access each type of parameters as follows:

```php
<?php

$id = $request->query_params['id'];
$method = $request->request_params['method'];
$info = $request->path_params['info'];
```

All the request parameters are also available through the `params` property, which is a merge of
the _query_, _request_ and _path_ parameters:

```php
<?php

$id = $request->params['id'];
$method = $request->params['method'];
$info = $request->params['info'];
```

The request parameters are also available by using the request as an array, in which case
accessing undefined parameters simply returns `null`:

```php
<?php

$id = $request['id'];
$method = $request['method'];
$info = $request['info'];

var_dump($request['undefined']); // null
```

Of course, the request is also an iterator:

```php
<?php

foreach ($request as $parameter => $value)
{
	echo "$parameter: $value\n";
}
```





### Request files

Files associated with a request are collected in a [FileList][] instance. The initial request
created with `$_SERVER` obtain its files from `$_FILES`. For custom requests files are defined
using the `files` keyword.

```php
<?php

$request = Request::from($_SERVER);

# or

$request = Request::from([

	'files' => [

		'uploaded' => [ 'pathname' => '/path/to/my/example.zip' ]

	]

]);

#

$files = $request->files;    // instanceof FileList
$file = $files['uploaded'];  // instanceof File
$file = $files['undefined']; // null
```

Uploaded files, and _pretend_ uploaded files, are represented by [File][] instances. The class
tries its best to provide the same API for both. The `is_uploaded` property lets you distinguish
uploaded files from _pretend_ uploaded files.

The `is_valid` property is a simple way to check if a file is valid. The `move()` method
let's you move the file out of the temporary folder or around the filesystem.

```php
<?php

echo $file->name;            // example.zip
echo $file->unsuffixed_name; // example
echo $file->extension;       // .zip
echo $file->size;            // 1234
echo $file->type;            // application/zip
echo $file->is_uploaded;     // false

if ($file->is_valid)
{
	// `true` means that the destination file will be overwritten
	$file->move('/path/to/repository/' . $file->name, true);
}
```

The `match()` method is used to check if a file matches a MIME type, a MIME class, or a file
extension:

```php
<?php

echo $file->match('application/zip');             // true
echo $file->match('application');                 // true
echo $file->match('.zip');                        // true
echo $file->match('image/png')                    // false
echo $file->match('image')                        // false
echo $file->match('.png')                         // false
```

The method also handles sets, and returns `true` if there's any match:

```php
echo $file->match([ '.png', 'application/zip' ]); // true
echo $file->match([ '.png', '.zip' ]);            // true
echo $file->match([ 'image/png', '.zip' ]);       // true
echo $file->match([ 'image/png', 'text/plain' ]); // false
```

[File][] instances implements the [ToArray][] interface and can be converted into arrays
with the `to_array()` method:

```php
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





## Response

The response to a request is represented by a [Response](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Response.html) instance.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response('<!DOCTYPE html><html><body><h1>Hello world!</h1></body></html>', 200, [

	'Content-Type' => 'text/html',
	'Cache-Control' => 'public, max-age=3600'

]);
```

The response is returned simply by invoking it:

```php
<?php

$response();
```





## Headers

HTTP headers are represented by a [Headers](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.html)
instance. There are used by requests and responses, but can also be used to create the headers
string of the `mail()` command.





### Content-Type header

The `Content-Type` header is represented by a [ContentTypeHeader](http://icanboogie.org/docs/class-ICanBoogie.HTTP.ContentTypeHeader.html)
instance making it easily manipulate.

```php
<?php

$response->headers['Content-Type'] = 'text/html; charset=utf-8';

echo $response->headers['Content-Type']->type; // text/html
echo $response->headers['Content-Type']->charset; // utf-8

$response->headers['Content-Type']->type = 'application/xml';

echo $response->headers['Content-Type']; // application/xml; charset=utf-8
```





### Content-Disposition header

The `Content-Disposition` header is represented by a [ContentDispositionHeader](http://icanboogie.org/docs/class-ICanBoogie.HTTP.ContentDispositionHeader.html)
instance making it easily manipulate. Accentuated filenames are supported.

```php
<?php

$response->headers['Content-Disposition'] = 'attachment; filename="été.jpg"';

echo $response->headers['Content-Disposition']->type; // attachment
echo $response->headers['Content-Disposition']->filename; // été.jpg

echo $response->headers['Content-Disposition']; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg
```





### Cache-Control header

The `Cache-Control` header is represented by a [CacheControlHeader](http://icanboogie.org/docs/class-ICanBoogie.HTTP.CacheControlHeader.html)
instance making it easily manipulable. Directives can be set at once using a plain string,
or individually using the properties of the [CacheControlHeader](http://icanboogie.org/docs/class-ICanBoogie.HTTP.CacheControlHeader.html) instance.
Directives of the [rfc2616](http://www.w3.org/Protocols/rfc2616/rfc2616.html) are supported.

```php
<?php

$response->headers['Cache-Control'] = 'public, max-age=3600, no-transform';

echo $response->headers['Cache-Control']; // public, max-age=3600, no-transform
echo $response->headers['Cache-Control']->cacheable; // public
echo $response->headers['Cache-Control']->max_age; // 3600
echo $response->headers['Cache-Control']->no_transform; // true

$response->headers['Cache-Control']->no_transform = false;
$response->headers['Cache-Control']->max_age = 7200;

echo $response->headers['Cache-Control']; // public, max-age=7200
```





### Date, Expires, If-Modified-Since, If-Unmodified-Since and Retry-After headers

All date related headers can be specified as Unix timestamp, strings or `DateTime` instances.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response('{ "message": "Ok" }', 200, [

	'Content-Type' => 'application/json',
	'Date' => 'now',
	'Expires' => '+1 hour'

]);
```




## Dispatcher

Requests are handled using a [Dispatcher][] instance, but dispite the many features of the
dispatcher, it is incapable of resolving the request into a response by itself, instead it
relies on dispatcher plugins and events.





### Dispatcher plugins

Wrapped in the comfort of the dispatcher, dispatcher plugins are the ones who really handle the
requests. They may be instances of classes implementing the [IDispatcher][] interface or
callables, and they usually handle a very specific type of request.

As an example, the following dispatcher plugins are used by the CMS Icybee:

- `operation`: Defined by the `icanboogie/operation` package, it handles operations.
- `routes`: Defined by the `icanboogie/routing` package, it handles routes defined using
the `routes` configuration.
- `pages`: Defined by the `icybee/pages` package, it handles managed pages.

The following code demonstrates how a [Dispatcher][] instance can be created with these dispatcher
plugins.

```php
<?php

use ICanBoogie\HTTP\Dispatcher;

$dispatcher = new Dispatcher([

	'operation' => 'ICanBoogie\Operation\Dispatcher',
	'route' => 'ICanBoogie\Routing\Dispatcher',
	'page' => 'Icybee\Modules\Pages\PageController'

]);
```





### Weighted dispatcher plugins

The order in which the dispatcher plugins are defined is important because each one of them is
invoked in turn until one returns a response or throws an exception. Some dispatcher plugins
might need to run before others, in that case they need to be defined using a
`WeightedDispatcher` instance.

The weight is defined as an integer; the special values `top` or `bottom`; or a position relative
to a target. Consider the following example:

```php
<?php

$dispatcher = new Dispatcher([

	'two' => 'dummy',
	'three' => 'dummy'

]);

$dispatcher['bottom']      = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['megabottom']  = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['hyperbottom'] = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['one']         = new WeightedDispatcher('dummy', 'before:two');
$dispatcher['four']        = new WeightedDispatcher('dummy', 'after:three');
$dispatcher['top']         = new WeightedDispatcher('dummy', 'top');
$dispatcher['megatop']     = new WeightedDispatcher('dummy', 'top');
$dispatcher['hypertop']    = new WeightedDispatcher('dummy', 'top');

$order = '';

foreach ($dispatcher as $dispatcher_id => $dummy)
{
	$order .= ' ' . $dispatcher_id;
}

echo $order; //  hypertop megatop top one two three four bottom megabottom hyperbottom
```

Notice how the `before:` and `after:` prefixes are used to indicate how the dispatcher plugins
should be ordered relatively to the specified targets.





## Dispatching requests

When a dispatcher is asked to handle a request, it invokes each of its dispatcher plugins in turn
until one returns a [Response][] instance or throws an exception. If an exception is thrown during
the dispatch, the dispatcher tries to _rescue_ it using either the dispatcher plugin's `rescue()`
method or the event system. Around that, events are fired to allow third parties to alter the
request and alter or replace the response. Finally, if the request could not be resolved into a
response a [NotFound][] exception is thrown, otherwise the response is returned.

```php
<?php

$request = Request::from('/api/core/ping');

try
{
	$response = $dispatcher($request);
	$response();
}
catch (NotFound $e)
{
	echo $e->getMessage();
}
```





### Before a request is dispatched

The `ICanBoogie\HTTP\Dispatcher::dispatch:before` event of class [BeforeDispatchEvent][]
is fired before a request is dispatched.

Third parties may use this event to provide a response to the request before the dispatcher plugins
are invoked. If a response is provided the dispatcher plugins are skipped.

The event is usually used to redirect requests or provide cached responses. The following code
demonstrates how a request could be redirected if its path is not normalized. For instance a
request for "/index.html" would be redirected to "/".

```php
<?php

use ICanBoogie\Event;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\RedirectResponse;

$events->attach(function(Dispatcher\BeforeDispatchEvent $event, Dispatcher $dispatcher) {

	$path = $event->request->path;
	$normalized_path = $event->request->normalized_path;

	if ($path === $normalized_path)
	{
		return;
	}

	$event->response = new RedirectResponse($normalized_path);
	$event->stop();

});
```

Notice how the `stop()` method of the event is invoked to stop the event propagation and
prevent other event hooks from altering the response.





### After a request was dispatched

The `ICanBoogie\HTTP\Dispatcher::dispatch` event of class [DispatchEvent][] is fired after a
request was dispatched, even if no response was provided by dispatcher plugins.

Third parties may use this event to alter or replace the response before it is returned by the
dispatcher. The following code demonstrates how a cache could be updated after a response with
the content type "text/html" was found for a request.

```php
<?php

use ICanBoogie\Event;
use ICanBoogie\HTTP\Dispatcher;

$events->attach(function(Dispatcher\DispatchEvent $event, Dispatcher $target) use($cache) {

	$response = $event->response;

	if ($response->content_type->type !== 'text/html')
	{
		return;
	}

	$cache[sha1($event->request->uri)] = $event->response;

});
```





### Rescuing exceptions

Most likely your application is going to throw exceptions, whether they are caused by software
bugs or logic, you might want to handle them. For example, you might want to present a login form
instead of the default exception message when a [AuthenticationRequired][] exception is thrown.

Exceptions can be rescued at two levels: the dispatcher plugin level, using its `rescue()`
method; or the main dispatcher level, by listening to the `Exception::rescue` event.

Third parties may use the `Exception::rescue` event of class [RescueEvent][] to provide a response
for an exception. The following example demonstrates how a login form can be returned as response
when a [AuthenticationRequired][] exception is thrown.

```php
<?php

use ICanBoogie\Event;
use ICanBoogie\HTTP\Response;

$events->attach(function(ICanBoogie\Exception\RescueEvent $event, ICanBoogie\AuthenticationRequired $target) {

	ICanBoogie\log_error($target->getMessage());

	$event->response = new Response(new DocumentDecorator(new LoginForm), $target->getCode());
	$event->stop();

});
```





#### The `X-ICanBoogie-Rescued-Exception` header field

The `X-ICanBoogie-Rescued-Exception` header field is added to the response obtained while rescuing
an exception, it indicates the origin of the exception, this might help you while tracking bugs.

Note that the origin path of the exception is relative to the `DOCUMENT_ROOT`.





#### Force redirect

If they are not rescued during the `Exception::rescue` event, [ForceRedirect][] exceptions are
resolved into [RedirectResponse][] instances.





### A second chance for `HEAD` operations

When a request with a `HEAD` method fails to get a response (a [NotFound][] exception was
thrown) the dispatcher tries the same request with a `GET` method instead. If a response is
provided a new response is returned with only its status and headers but with an empty body,
otherwise the dispatcher tries to rescue the exception.

Leveragin this feature, you won't have to implement a controller for the `HEAD` method if the
controller for the `GET` method is good enough.





## Exceptions

The following exceptions are defined by the HTTP package:

* [HTTPError](http://icanboogie.org/docs/class-ICanBoogie.HTTP.HTTPError.html): Base class for HTTP exceptions.
* [NotFound][]: Exception thrown when a resource is not found. For instance, this exception is
thrown by the dispatcher when it fails to resolve a request into a response.
* [ForceRedirect][]: Exception thrown when a redirect is absolutely required.
* [ServiceUnavailable](http://icanboogie.org/docs/class-ICanBoogie.HTTP.ServiceUnavailable.html): Exception thrown when the server is currently unavailable
(because it is overloaded or down for maintenance).
* [MethodNotSupported](http://icanboogie.org/docs/class-ICanBoogie.HTTP.MethodNotSupported.html): Exception thrown when a HTTP method is not supported.
* [StatusCodeNotValid](http://icanboogie.org/docs/class-ICanBoogie.HTTP.StatusCodeNotValid.html): Exception thrown when a HTTP status code is not valid.





## Helpers

The following helpers are available:

* [dispatch](http://icanboogie.org/docs/function-ICanBoogie.HTTP.dispatch.html): Dispatches a request using the dispatcher returned by `get_dispatcher()`.
* [get_dispatcher](http://icanboogie.org/docs/function-ICanBoogie.HTTP.get_dispatcher.html): Returns the main dispatcher.
* [get_initial_request](http://icanboogie.org/docs/function-ICanBoogie.HTTP.get_initial_request.html): Returns the initial request.





### Patching helpers

Helpers can be patched using the `Helpers::patch()` method.

The following code demonstrates how [ICanBoogie](http://icanboogie.org) patches the
`get_dispatcher()` helper to provide its own dispatcher, which is initialized with some
dispatcher plugins:

```php
<?php

namespace ICanBoogie;

use ICanBoogie\HTTP\Dispatcher;

ICanBoogie\HTTP\Helpers::patch('get_dispatcher', function() {

	static $dispatcher;

	if (!$dispatcher)
	{
		$dispatcher = new Dispatcher([

			'operation' => 'ICanBoogie\Operation\Dispatcher',
			'route' => 'ICanBoogie\Routing\Dispatcher'

		]);

		new Dispatcher\AlterEvent($dispatcher);
	}

	return $dispatcher;

});

namespace ICanBoogie\HTTP\Dispatcher;

use ICanBoogie\HTTP\Dispatcher;

/**
 * Event class for the `ICanBoogie\HTTP\Dispatcher::alter` event.
 *
 * Third parties may use this event to register additionnal dispatchers.
 */
class AlterEvent extends \ICanBoogie\Event
{
	/**
	 * The event is constructed with the type `alter`.
	 *
	 * @param Dispatcher $target
	 */
	public function __construct(Dispatcher $target)
	{
		parent::__construct($target, 'alter');
	}
}
```





----------





## Requirements

The package requires PHP 5.4 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
	"minimum-stability": "dev",
	"require": {
		"icanboogie/http": "2.x"
	}
}
```

The following packages are required, you might want to check them out:

- [icanboogie/prototype](https://github.com/ICanBoogie/Prototype)
- [icanboogie/event](https://github.com/ICanBoogie/Event)
- [icanboogie/datetime](https://github.com/ICanBoogie/DateTime)





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/HTTP), its repository can be
cloned with the following command line:

	$ git clone git://github.com/ICanBoogie/HTTP.git





## Documentation

The package is documented as part of the [ICanBoogie](http://icanboogie.org/) framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all dependencies required to run the suite. You can later
clean the directory with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://travis-ci.org/ICanBoogie/HTTP.svg?branch=2.1)](https://travis-ci.org/ICanBoogie/HTTP)





## License

ICanBoogie/HTTP is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[BeforeDispatchEvent]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.BeforeDispatchEvent.html
[DispatchEvent]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.DispatchEvent.html
[Dispatcher]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.html
[IDispatcher]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.IDispatcher.html
[Response]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.Response.html
[RedirectResponse]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.RedirectResponse.html
[NotFound]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.NotFound.html
[File]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.File.html
[FileList]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.FileList.html
[ForceRedirect]: http://icanboogie.org/docs/class-ICanBoogie.HTTP.ForceRedirect.html
[RescueEvent]: http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html
[ToArray]: http://icanboogie.org/docs/class-ICanBoogie.ToArray.html
[AuthenticationRequired]: http://icanboogie.org/docs/class-ICanBoogie.AuthenticationRequired.html