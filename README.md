# HTTP

[![Release](https://img.shields.io/packagist/v/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)
[![Build Status](https://img.shields.io/travis/ICanBoogie/HTTP/master.svg)](http://travis-ci.org/ICanBoogie/HTTP)
[![HHVM](https://img.shields.io/hhvm/icanboogie/http.svg)](http://hhvm.h4cc.de/package/icanboogie/http)
[![Code Quality](https://img.shields.io/scrutinizer/g/ICanBoogie/HTTP/master.svg)](https://scrutinizer-ci.com/g/ICanBoogie/HTTP)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/HTTP/master.svg)](https://coveralls.io/r/ICanBoogie/HTTP)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)

The HTTP package provides an API to handle HTTP requests.





## Request

A request is represented by a [Request][] instance. The initial request is usually created from the `$_SERVER` array, while sub requests are created from arrays of properties.

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





### A request with changed properties

Requests are for the most part immutable, the `with()` method creates an instance copy with changed properties.

```php
<?php

use ICanBoogie\HTTP\Request;

$request = Request::from($_SERVER)->with([

	'is_head' => true,
	'is_xhr' => true

]);
```




### Request parameters

Whether they are sent as part of the query string, the post body or the path info, parameters sent along a request are collected in arrays. The `query_params`, `request_params` and `path_params` properties give you access to these parameters.

You can access each type of parameter as follows:

```php
<?php

$id = $request->query_params['id'];
$method = $request->request_params['method'];
$info = $request->path_params['info'];
```

All the request parameters are also available through the `params` property, which merges the _query_, _request_ and _path_ parameters:

```php
<?php

$id = $request->params['id'];
$method = $request->params['method'];
$info = $request->params['info'];
```

Used as an array, the [Request][] instance provides these parameters as well, but returns `null` when a parameter is not defined:

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
	$file->move('/path/to/repository/' . $file->name, File::MOVE_OVERWRITE);
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

[File][] instances implement the [ToArray][] interface and can be converted into arrays
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





### Obtaining a response

A response is obtained from a request simply by invoking the request, or by invoking one of the available HTTP methods. The `dispatch()` helper is used to dispatch the request. A [Response][] instance is returned if the dispatching is successful, a [NotFound][] exception is  thrown otherwise.

```php
<?php

$response = $request();

# using the POST method and additional parameters

$response = $request->post([ 'param' => 'value' ]);
```





## Response

The response to a request is represented by a [Response][] instance. The response body can either be a `null`, a string, an object implementing `__toString()`, or a closure.

**Note:** Contrary to [Request][] instances, [Response][] instances or completely mutable.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response('<!DOCTYPE html><html><body><h1>Hello world!</h1></body></html>', 200, [

	'Content-Type' => 'text/html',
	'Cache-Control' => 'public, max-age=3600'

]);
```




### Response status

The response status is represented by a [Status][] instance. It can be defined as a HTTP response code such as `200`, an array such as `[ 200, "Ok" ]`, or a string such as `"200 Ok"`.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response;

echo $response->status;               // 200 Ok
echo $response->status->code;         // 200
echo $response->status->message;      // Ok
$response->status->is_valid;          // true

$response->status = 404;
echo $response->status->code;         // 404
echo $response->status->message;      // Not Found
$response->status->is_valid;          // false
$response->status->is_client_error;   // true
$response->status->is_not_found;      // true
```





### Streaming the response body

When large response body needs to be issued, it is recommended to use a closure as response body instead of a huge string that will consume a lot of memory.

```php
<?php

use ICanBoogie\HTTP\Response;

$fh = fopen('/path/to/my/huge/file', 'rb');
$output = function() use ($fh)
{
	fseek($fh, 0);

	if (!ini_get('safe_mode'))
	{
		set_time_limit(0);
	}

	while (!feof($fh) && !connection_aborted())
	{
		echo fread($fh, 1024 * 8);

		flush();
	}

	fclose($fh);
};

return new Response($output, 200);
```





### About `Content-Length` header field

Before v2.3.2 the `Content-Length` header field was added automatically when it was computable, for instance when the body was a string or an instance implementing `__toString()`. Starting v2.3.2 this is no longer the case and the header field has to be defined when required. This was decided to prevent a bug with Apache+FastCGI+DEFLATE where the `Content-Length` field was not adjusted although the body was compressed. Also, in most cases it's not such a good idea to define that field for generated content because it prevents the response to be send as [compressed chunks](http://en.wikipedia.org/wiki/Chunked_transfer_encoding).





### Returning a response

A response is returned simply by invoking it:

```php
<?php

$response();
```





### Redirect response

A redirect response can be easily created using a [RedirectResponse][] instance.

```php
<?php

use ICanBoogie\HTTP\RedirectResponse;

$response = new RedirectResponse('/to/redirect/location');
$response->status->code;        // 302
$response->status->is_redirect; // true
```





## Headers

HTTP headers are represented by a [Headers][] instance. There are used by requests and
responses, but can also be used to create the headers string of the `mail()` command.





### Content-Type header

The `Content-Type` header is represented by a [ContentType][] instance making it easily manipulate.

```php
<?php

$response->headers['Content-Type'] = 'text/html; charset=utf-8';

echo $response->headers['Content-Type']->type; // text/html
echo $response->headers['Content-Type']->charset; // utf-8

$response->headers['Content-Type']->type = 'application/xml';

echo $response->headers['Content-Type']; // application/xml; charset=utf-8
```





### Content-Disposition header

The `Content-Disposition` header is represented by a [ContentDisposition][] instance making it
easily manipulate. Accentuated file names are supported.

```php
<?php

$response->headers['Content-Disposition'] = 'attachment; filename="été.jpg"';

echo $response->headers['Content-Disposition']->type; // attachment
echo $response->headers['Content-Disposition']->filename; // été.jpg

echo $response->headers['Content-Disposition']; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg
```





### Cache-Control header

The `Cache-Control` header is represented by a [CacheControl][] instance making it easily
manipulable. Directives can be set at once using a plain string, or individually using the
properties of the [CacheControl][] instance. Directives of the
[rfc2616](http://www.w3.org/Protocols/rfc2616/rfc2616.html) are supported.

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




## Request dispatcher

A [RequestDispatcher][] instance dispatches the requests using a collection of _domain dispatchers_, for which The _request dispatcher_ provides a nice framework. The _request dispatcher_ sorts _domain dispatchers_ according to their weight, fire events, and tries to rescue exceptions should they occur.





### Domain dispatchers

A _domain dispatcher_ handles a very specific type of request. It may be an instance implementing the [Dispatcher][] interface, or simple callable.

The following example demonstrates how a [RequestDispatcher][] instance may be created with several _domain dispatchers_:

- `operation`: Defined by the `icanboogie/operation` package, handles operations.
- `routes`: Defined by the `icanboogie/routing` package, handles routes defined using
the `routes` configuration.
- `pages`: Defined by the `icybee/pages` package, handles managed pages.

```php
<?php

use ICanBoogie\HTTP\RequestDispatcher;

$dispatcher = new RequestDispatcher([

	'operation' => 'ICanBoogie\Operation\OperationDispatcher',
	'route' => 'ICanBoogie\Routing\RouteDispatcher',
	'page' => 'Icybee\Modules\Pages\PageDisptacher'

]);
```





### Weighted domain dispatchers

The order in which the dispatcher plugins are defined is important because each one of them is
invoked in turn until one returns a response or throws an exception. Some dispatcher plugins
might need to run before others, in that case they need to be defined using a
`WeightedDispatcher` instance.

The weight is defined as an integer; the special values `top` or `bottom`; or a position relative
to a target. Consider the following example:

```php
<?php

use ICanBoogie\HTTP\RequestDispatcher;

$dispatcher = new RequestDispatcher([

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

When the _request dispatcher_ is asked to handle a request, it invokes each of its _domain dispatchers_ in turn
until one returns a [Response][] instance or throws an exception. If an exception is thrown during
the dispatch, the _request dispatcher_ tries to _rescue_ it using either the _domain dispatcher's_ `rescue()`
method or the event system. Around that, events are fired to allow event hooks to alter the
request, or alter or replace the response. Finally, if the request could not be resolved into a
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

The `ICanBoogie\HTTP\RequestDispatcher::dispatch:before` event of class [BeforeDispatchEvent][]
is fired before a request is dispatched.

Third parties may use this event to provide a response to the request before the dispatcher plugins
are invoked. If a response is provided the dispatcher plugins are skipped.

The event is usually used to redirect requests or provide cached responses. The following code
demonstrates how a request could be redirected if its path is not normalized. For instance a
request for "/index.html" would be redirected to "/".

```php
<?php

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\RedirectResponse;

$events->attach(function(RequestDispatcher\BeforeDispatchEvent $event, RequestDispatcher $dispatcher) {

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

The `ICanBoogie\HTTP\RequestDispatcher::dispatch` event of class [DispatchEvent][] is fired after a
request was dispatched, even if no response was provided by dispatcher plugins.

Third parties may use this event to alter or replace the response before it is returned by the
dispatcher. The following code demonstrates how a cache could be updated after a response with
the content type "text/html" was found for a request.

```php
<?php

use ICanBoogie\HTTP\RequestDispatcher;

$events->attach(function(RequestDispatcher\DispatchEvent $event, RequestDispatcher $target) use($cache) {

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





### A second chance for `HEAD` requests

When a request with a `HEAD` method fails to get a response (a [NotFound][] exception was
thrown) the dispatcher tries the same request with a `GET` method instead. If a response is
provided a new response is returned with only its status and headers but with an empty body,
otherwise the dispatcher tries to rescue the exception.

Leveraging this feature, you won't have to implement a controller for the `HEAD` method if the
controller for the `GET` method is good enough.





### Stripping the body of responses to `HEAD` requests

The dispatcher cares about responses to `HEAD` requests and will strip responses of their body
before returning them.





## Exceptions

The following exceptions are defined by the HTTP package:

* [NotFound][]: thrown when a resource is not found. For instance, this exception is
thrown by the dispatcher when it fails to resolve a request into a response.
* [ForceRedirect][]: thrown when a redirect is absolutely required.
* [ServiceUnavailable](http://api.icanboogie.org/http/class-ICanBoogie.HTTP.ServiceUnavailable.html): thrown when a server is currently unavailable
(because it is overloaded or down for maintenance).
* [MethodNotSupported](http://api.icanboogie.org/http/class-ICanBoogie.HTTP.MethodNotSupported.html): thrown when a HTTP method is not supported.
* [StatusCodeNotValid](http://api.icanboogie.org/http/class-ICanBoogie.HTTP.StatusCodeNotValid.html): thrown when a HTTP status code is not valid.

All the exceptions defined by the package implement the `ICanBoogie\HTTP\Exception` interface.
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

* [dispatch](http://api.icanboogie.org/http/function-ICanBoogie.HTTP.dispatch.html): Dispatches a request using the dispatcher returned by `get_dispatcher()`.
* [get_dispatcher](http://api.icanboogie.org/http/function-ICanBoogie.HTTP.get_dispatcher.html): Returns the main dispatcher.
* [get_initial_request](http://api.icanboogie.org/http/function-ICanBoogie.HTTP.get_initial_request.html): Returns the initial request.





### Altering the dispatcher

The `ICanBoogie\HTTP\RequestDispatcher::alter` event of class [ICanBoogie\HTTP\RequestDispatcher\AlterEvent][] is fired after the dispatcher has been created. Third parties may use this event to register or alter dispatchers, or replace the dispatcher altogether.

The following code illustrate how a `hello` dispatcher, that returns
"Hello world!" when the request matches the path "/hello", can be registered.

```php
<?php

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

$app->events->attach(function(RequestDispatcher\AlterEvent $event, RequestDispatcher $target) {

	$target['hello'] = function(Request $request) {

		if ($request->path === '/hello')
		{
			return new Response('Hello world!');
		}
		
	}

});
```





### Patching helpers

Helpers can be patched using the `Helpers::patch()` method.

The following code demonstrates how [ICanBoogie](http://icanboogie.org) patches the
`get_dispatcher()` helper to provide its own dispatcher, which is initialized with some
dispatcher plugins:

```php
<?php

namespace ICanBoogie;

use ICanBoogie\HTTP\RequestDispatcher;

ICanBoogie\HTTP\Helpers::patch('get_dispatcher', function() {

	static $dispatcher;

	if (!$dispatcher)
	{
		$dispatcher = new RequestDispatcher([

			'operation' => 'ICanBoogie\Operation\RequestDispatcher',
			'route' => 'ICanBoogie\Routing\RequestDispatcher'

		]);

		new RequestDispatcher\AlterEvent($dispatcher);
	}

	return $dispatcher;

});

namespace ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\HTTP\RequestDispatcher;

/**
 * Event class for the `ICanBoogie\HTTP\RequestDispatcher::alter` event.
 *
 * Third parties may use this event to register additional dispatchers.
 */
class AlterEvent extends \ICanBoogie\Event
{
	/**
	 * The event is constructed with the type `alter`.
	 *
	 * @param RequestDispatcher $target
	 */
	public function __construct(RequestDispatcher $target)
	{
		parent::__construct($target, 'alter');
	}
}
```





----------





## Requirements

The package requires PHP 5.5 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/):

```
$ composer require icanboogie/http
```

The following packages are required, you might want to check them out:

- [icanboogie/prototype](https://github.com/ICanBoogie/Prototype)
- [icanboogie/event](https://github.com/ICanBoogie/Event)
- [icanboogie/datetime](https://github.com/ICanBoogie/DateTime)





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/HTTP), its repository can be
cloned with the following command line:

	$ git clone https://github.com/ICanBoogie/HTTP.git





## Documentation

The package is documented as part of the [ICanBoogie][] framework
[documentation](http://api.icanboogie.org/http/). You can generate the documentation for the package and its dependencies with the `make doc` command. The documentation is generated in the `build/docs` directory. [ApiGen](http://apigen.org/) is required. The directory can later be cleaned with the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [PHPUnit](https://phpunit.de/) and [Composer](http://getcomposer.org/) need to be globally available to run the suite. The command installs dependencies as required. The `make test-coverage` command runs test suite and also creates an HTML coverage report in `build/coverage`. The directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://img.shields.io/travis/ICanBoogie/HTTP/master.svg)](https://travis-ci.org/ICanBoogie/HTTP)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/HTTP/master.svg)](https://coveralls.io/r/ICanBoogie/HTTP)





## License

**icanboogie/http** is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[BeforeDispatchEvent]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.RequestDispatcher.BeforeDispatchEvent.html
[CacheControl]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Headers.CacheControl.html
[ContentDisposition]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Headers.ContentDisposition.html
[ContentType]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Headers.ContentType.html
[DispatchEvent]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.RequestDispatcher.DispatchEvent.html
[RequestDispatcher]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.RequestDispatcher.html
[Dispatcher]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Dispatcher.html
[Headers]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Headers.html
[Response]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Response.html
[RedirectResponse]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.RedirectResponse.html
[NotFound]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.NotFound.html
[File]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.File.html
[FileList]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.FileList.html
[ForceRedirect]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.ForceRedirect.html
[Request]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Request.html
[RescueEvent]: http://api.icanboogie.org/http/class-ICanBoogie.Exception.RescueEvent.html
[Status]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Status.html
[ToArray]: http://api.icanboogie.org/common/class-ICanBoogie.ToArray.html
[AuthenticationRequired]: http://api.icanboogie.org/icanboogie/class-ICanBoogie.AuthenticationRequired.html
