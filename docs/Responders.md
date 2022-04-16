# Responders

A responder takes a request and returns a response. It's a simple concept that can be implemented in many ways, of
which this package only provides the foundation.

This package only provides an implementation that delegates the response to a matching responder found by a provider.
The package [ICanBoogie/Routing][] provides more exciting implementations.

```php
<?php

namespace ICanBoogie\HTTP;

/* @var ResponderProvider $responders */
/* @var Request $request */

$responder = new Responder\WithProvider($provider);
$response = $responder->respond($request);
```



[ICanBoogie/Routing]: https://github.com/ICanBoogie/Routing
