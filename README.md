# HTTP [![Build Status](https://secure.travis-ci.org/ICanBoogie/HTTP.png?branch=master)](http://travis-ci.org/ICanBoogie/HTTP)

The HTTP package provides an API to handle HTTP requests.





## Requirements

The package requires PHP 5.3 or later. The following packages are required:
[icanboogie/prototype](https://packagist.org/packages/icanboogie/prototype) and
[icanboogie/event](https://packagist.org/packages/icanboogie/event).





## Installation

The recommended way to install this package is through [composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
    "minimum-stability": "dev",
    "require": {
		"icanboogie/http": "1.0.*"
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





## Request – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Request.html)

A request is represented by a `ICanBoogie\HTTP\Request` instance. The initial request is usually
created from the `$_SERVER` array, while sub requests can be created from array of properties.

```php
<?php

use ICanBoogie\HTTP\Request;

$initial_request = Request::from($_SERVER);

# or

$request = Request::from(array('path' => 'path/to/file.html'));
```

Request are dispatched by a dispatcher, which should return a `ICanBoogie\HTTP\Response` object.
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




## Response – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Response.html)

The response to a request is represented by a `ICanBoogie\HTTP\Response` instance.

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





## Headers – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.html)

HTTP headers are represented by a `ICanBoogie\HTTP\Headers` instance. There are used by requests
and responses, but can also be used to create the headers string of the `mail()` command.





### Content-Type header – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.ContentType.html)

The `Content-Type` header is easily manipulate.

```php
<?php

$response->headers['Content-Type'] = 'text/html; charset=utf-8';

echo $response->headers['Content-Type']->type; // text/html
echo $response->headers['Content-Type']->charset; // utf-8

$response->headers['Content-Type']->type = 'application/xml'; 

echo $response->headers['Content-Type']; // application/xml; charset=utf-8
```





### Content-Disposition header – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.ContentDisposition.html)

The `Content-Disposition` header is easily manipulate, and accentuated filenames are supported. 

```php
<?php

$response->headers['Content-Disposition'] = 'attachment; filename="été.jpg"';

echo $response->headers['Content-Disposition']->type; // attachment
echo $response->headers['Content-Disposition']->filename; // été.jpg

echo $response->headers['Content-Disposition']; // attachment; filename="ete.jpg"; filename*=UTF-8''%C3%A9t%C3%A9.jpg
```





### Cache-Control header – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Headers.CacheControl.html)

The `Cache-Control` header is easily manipulable. Directives can be set at once using a plain string,
or individually using the properties of the `CacheControl` instance.
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

Request are dispatched using a `ICanBoogie\HTTP\Dispatcher` instance. The dispatcher uses user
defined dispatchers to try to get a response for a request.





### Rescue

Most likely your application is going to throw exceptions, whether they are caused by software
bugs or logic, you might want to handle them. For example, to present a login form when the
`AuthenticationRequired` exception is thrown instead of the default exception message.

The exception can be rescued at two levels: the user dispatcher level, using its `rescue()`
method; or the main dispatcher level, by listening to the `Exception::rescue` event.





## Events

### A dispatcher is instantiated – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.CollectEvent.html)

The `ICanBoogie\HTTP\Dispatcher::collect` event of class
`ICanBoogie\HTTP\Dispatcher\CollectEvent` is fired when the dispatcher is instantiated.

Third parties may use this event to register dispatchers or alter initial dispatchers.

```php
<?php

use ICanBoogie\Events;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

Events::attach('ICanBoogie\HTTP\Dispatcher::collect', function(Dispatcher\CollectEvent $event) {

	$event->dispatchers['hello'] = function(Request $request) {
	
		if ($request->path === 'hello')
		{
			return new Response(200, array(), 'Hello world!');
		}
	}

});
```





### Before a request is dispatched – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.BeforeDispatchEvent.html)

The `ICanBoogie\HTTP\Dispatcher::dispatch:before` event of class
`ICanBoogie\HTTP\Dispatcher\BeforeDispatchEvent` is fired before a request is dispatched. 

Third parties may use this event to provide a response to the request before the dispatchers
are invoked. If a response is provided the dispatchers are skipped. The event is usually used
to redirect requests or provide cached responses.

```php
<?php

use ICanBoogie\Events;
use ICanBoogie\HTTP\Dispatcher;
use ICanBoogie\HTTP\RedirectResponse;

Events::attach('ICanBoogie\HTTP\Dispatcher::dispatch:before', function(Dispatcher\BeforeDispatchEvent $event) {

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





### After a request was dispatched – [API](http://icanboogie.org/docs/class-ICanBoogie.HTTP.Dispatcher.DispatchEvent.html)

The `ICanBoogie\HTTP\Dispatcher::dispatch` event of class
`ICanBoogie\HTTP\Dispatcher\DispatchEvent` is fired after a request was dispatched. The event
is fired even if no response was provided by dispatchers.

Third parties may use this event to alter the response before it is returned by the dispatcher,
provide a default response if no response was provided, or cache the response.

```php
<?php

use ICanBoogie\Events;
use ICanBoogie\HTTP\Dispatcher;

Events::attach('ICanBoogie\HTTP\Dispatcher::dispatch', function(Dispatcher\DispatchEvent $event) use($cache) {

	$response = $event->response;
	
	if ($response->content_type->type !== 'text/html')
	{
		return;
	}

	$cache[sha1($event->request->uri)] = $event->response;

});
```





### An exception was thrown during dispatching – [API](http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html)

The `Exception:rescue` event of class `ICanBoogie\Exception\RescueEvent` is fired when an
exception is caught during a request dispatching.

Third parties may use this event to provide a response for the exception.

The following example demonstrates how a _login form_ can be returned as response when a
`ICanBoogie\AuthenticationRequired` exception is thrown.

```php
<?php

use ICanBoogie\Events;
use ICanBoogie\HTTP\Response;

Events::attach('ICanBoogie\AuthenticationRequired::rescue', function(ICanBoogie\Exception\RescueEvent $event, ICanBoogie\AuthenticationRequired $target)
{
	ICanBoogie\log_error($target->getMessage());

	$event->response = new Response($target->getCode(), array(), new LoginForm());
	$event->stop();
});
```





## Exceptions

The following exceptions are defined by the HTTP package:

* `HTTPError`: Base class for HTTP exceptions.
* `NotFound`: Exception thrown when a resource is not found.
* `ServiceUnavailable`: Exception thrown when the server is currently unavailable
(because it is overloaded or down for maintenance).
* `MethodNotSupported`: Exception thrown when the HTTP method is not supported.
* `StatusCodeNotValid`: Exception thrown when the HTTP status code is not valid.





## Helpers

The following helpers are available:

* `dispatch`: Dispatches a request.
* `get_dispatcher`: Returns shared request dispatcher.
* `get_initial_request`: Returns the initial request.





### Patching helpers

Helpers can be patched using the `Helpers::patch()` method. This is how [ICanBoogie](http://icanboogie.org)
patches the `get_dispatcher()` helper to use a unique request dispatcher, which is initialized
with some dispatchers:

```php
<?php

ICanBoogie\HTTP\Helpers::patch('get_dispatcher', function() {

	static $dispatcher;

	if (!$dispatcher)
	{
		$dispatcher = new HTTP\Dispatcher
		(
			array
			(
				'operation' => 'ICanBoogie\OperationDispatcher',
				'route' => 'ICanBoogie\RouteDispatcher'
			)
		);
	}

	return $dispatcher;

});
```





## License

ICanBoogie/HTTP is licensed under the New BSD License - See the LICENSE file for details.