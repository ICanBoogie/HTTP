# HTTP [![Build Status](https://secure.travis-ci.org/ICanBoogie/HTTP.png?branch=master)](http://travis-ci.org/ICanBoogie/HTTP)

The HTTP package provides an API to handle HTTP requests.





## Request

A request is represented by a [Request](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Request.html) instance.
The initial request is usually created from the `$_SERVER` array, while sub requests can be created
from array of properties.

```php
<?php

use ICanBoogie\HTTP\Request;

$initial_request = Request::from($_SERVER);

# or

$request = Request::from(array('path' => 'path/to/file.html'));
```

Requests are dispatched by a dispatcher, which should return a [Response](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Response.html) object.
Requests are usually dispatched simply by invoking them, or by using one of the HTTP methods
available:

```php
<?php

$response = $initial_request();

# or

$response = $request->post(array('arg' => 'value'));
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




### The request as an array

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




## Response

The response to a request is represented by a [Response](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Response.html) instance.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response
(
	'<!DOCTYPE html><html><body><h1>Hello world!</h1></body></html>', 200, array
	(
		'Content-Type' => 'text/html',
		'Cache-Control' => 'public, max-age=3600'
	)
);
```

The response is returned simply by invoking it:

```
<?php

$response();
```





## Headers

HTTP headers are represented by a [Headers](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.html)
instance. There are used by requests and responses, but can also be used to create the headers
string of the `mail()` command.





### Content-Type header

The `Content-Type` header is represented by a [ContentType](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.ContentType.html)
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

The `Content-Disposition` header represented by a [ContentDisposition](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.ContentDisposition.html)
instance making it easily manipulate. Accentuated filenames are supported. 

```php
<?php

$response->headers['Content-Disposition'] = 'attachment; filename="été.jpg"';

echo $response->headers['Content-Disposition']->type; // attachment
echo $response->headers['Content-Disposition']->filename; // été.jpg

echo $response->headers['Content-Disposition']; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg
```





### Cache-Control header

The `Cache-Control` header is represented by a [CacheControl](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.CacheControl.html)
instance making it easily manipulable. Directives can be set at once using a plain string,
or individually using the properties of the [CacheControl](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.CacheControl.html) instance.
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





### Date, Expires, If-Modified-Since, If-Unmodified-Since and Reply-After headers

All date related headers can be specified as Unix timestamp, strings or `DateTime` instances.

```php
<?php

use ICanBoogie\HTTP\Response;

$response = new Response
(
	'{ "message": "Ok" }', 200, array
	(
		'Content-Type' => 'application/json',
		'Date' => 'now',
		'Expires' => '+1 hour'
	) 
);
```





## Dispatching requests

Request are dispatched using a [Dispatcher](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.html)
instance. The dispatcher uses user defined dispatchers to try to get a response for a request.

```php
<?php

use ICanBoogie\HTTP\Dispatcher;

$dispatcher = new Dispatcher
(
	array
	(
		'operation' => 'ICanBoogie\Operation\Dispatcher',
		'route' => 'ICanBoogie\Routing\Dispatcher'
	)
);
```




### Weighted dispatchers

Some dispatchers might need to run before others, in this case they need to be defined using a
`WeightedDispatcher` instance. The weight can be defined as an integer, the special values `top`
or `bottom`, or a position relative to a target. Consider the following example:

```php
<?php

$dispatcher = new Dispatcher(array(

	'two' => 'dummy',
	'three' => 'dummy'

));

$dispatcher['bottom'] = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['megabottom'] = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['hyperbottom'] = new WeightedDispatcher('dummy', 'bottom');
$dispatcher['one'] = new WeightedDispatcher('dummy', 'before:two');
$dispatcher['four'] = new WeightedDispatcher('dummy', 'after:three');
$dispatcher['top'] = new WeightedDispatcher('dummy', 'top');
$dispatcher['megatop'] = new WeightedDispatcher('dummy', 'top');
$dispatcher['hypertop'] = new WeightedDispatcher('dummy', 'top');

$order = '';

foreach ($dispatcher as $dispatcher_id => $dummy)
{
	$order .= ' ' . $dispatcher_id;
}

echo $order; //  hypertop megatop top one two three four bottom megabottom hyperbottom
```





### Rescue

Most likely your application is going to throw exceptions, whether they are caused by software
bugs or logic, you might want to handle them. For example, to present a login form when the
[AuthenticationRequired](http://icanboogie.org/docs/class-ICanBoogie.AuthenticationRequired.html)
exception is thrown instead of the default exception message.

The exception can be rescued at two levels: the user dispatcher level, using its `rescue()`
method; or the main dispatcher level, by listening to the `Exception::rescue` event.





## Events

### Before a request is dispatched

The `ICanBoogie\HTTP\Dispatcher::dispatch:before` event of class
[BeforeDispatchEvent](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.BeforeDispatchEvent.html)
is fired before a request is dispatched. 

Third parties may use this event to provide a response to the request before the dispatchers
are invoked. If a response is provided the dispatchers are skipped. The event is usually used
to redirect requests or provide cached responses.

```php
<?php

use ICanBoogie\Event;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\RedirectResponse;

$events->attach(function(Dispatcher\BeforeDispatchEvent $event, Dispatcher $dispatcher) {

	$path = $event->request->path;
	$normalized_path = ICanBoogie\normalize_url_path($path);

	if ($path === $normalized_path)
	{
		return;
	}

	$event->response = new RedirectResponse($normalized_path);
	$event->stop();

});
```





### After a request was dispatched

The `ICanBoogie\HTTP\Dispatcher::dispatch` event of class
[DispatchEvent](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.DispatchEvent.html)
is fired after a request was dispatched. The event is fired even if no response was provided
by dispatchers.

Third parties may use this event to alter the response before it is returned by the dispatcher,
provide a default response if no response was provided, or cache the response.

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





### An exception was thrown during dispatching

The `Exception:rescue` event of class [RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html)
is fired when an exception is caught during a request dispatching.

Third parties may use this event to provide a response for the exception.

The following example demonstrates how a _login form_ can be returned as response when a
[AuthenticationRequired](http://icanboogie.org/docs/class-ICanBoogie.AuthenticationRequired.html) exception is thrown.

```php
<?php

use ICanBoogie\Event;
use ICanBoogie\HTTP\Response;

$events->attach(function(ICanBoogie\Exception\RescueEvent $event, ICanBoogie\AuthenticationRequired $target) {

	ICanBoogie\log_error($target->getMessage());

	$event->response = new Response($target->getCode(), array(), new LoginForm());
	$event->stop();

});
```





## Exceptions

The following exceptions are defined by the HTTP package:

* [HTTPError](http://icanboogie.org/docs/class-ICanBoogie.HTTP.HTTPError.html): Base class for HTTP exceptions.
* [NotFound](http://icanboogie.org/docs/class-ICanBoogie.HTTP.NotFound.html): Exception thrown when a resource is not found.
* [ServiceUnavailable](http://icanboogie.org/docs/class-ICanBoogie.HTTP.ServiceUnavailable.html): Exception thrown when the server is currently unavailable
(because it is overloaded or down for maintenance).
* [MethodNotSupported](http://icanboogie.org/docs/class-ICanBoogie.HTTP.MethodNotSupported.html): Exception thrown when the HTTP method is not supported.
* [StatusCodeNotValid](http://icanboogie.org/docs/class-ICanBoogie.HTTP.StatusCodeNotValid.html): Exception thrown when the HTTP status code is not valid.





## Helpers

The following helpers are available:

* [dispatch](http://icanboogie.org/docs/function-ICanBoogie.HTTP.dispatch.html): Dispatches a request using the main request dispatcher.
* [get_dispatcher](http://icanboogie.org/docs/function-ICanBoogie.HTTP.get_dispatcher.html): Returns the main request dispatcher.
* [get_initial_request](http://icanboogie.org/docs/function-ICanBoogie.HTTP.get_initial_request.html): Returns the initial request.





### Patching helpers

Helpers can be patched using the `Helpers::patch()` method. This is how [ICanBoogie](http://icanboogie.org)
patches the `get_dispatcher()` helper to provide its own request dispatcher, which is initialized
with some user dispatchers:

```php
<?php

ICanBoogie\HTTP\Helpers::patch('get_dispatcher', function() {

	static $dispatcher;

	if (!$dispatcher)
	{
		$dispatcher = new Dispatcher
		(
			array
			(
				'operation' => 'ICanBoogie\Operation\Dispatcher',
				'route' => 'ICanBoogie\Routing\Dispatcher'
			)
		);
		
		new Dispatcher\Alter($dispatchers);
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





## Requirements

The package requires PHP 5.3 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
    "minimum-stability": "dev",
    "require": {
		"icanboogie/http": "*"
    }
}
```





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

[![Build Status](https://travis-ci.org/ICanBoogie/HTTP.png?branch=master)](https://travis-ci.org/ICanBoogie/HTTP)





## License

ICanBoogie/HTTP is licensed under the New BSD License - See the LICENSE file for details.