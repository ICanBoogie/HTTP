# HTTP

[![Release](https://img.shields.io/packagist/v/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)
[![Code Coverage](https://coveralls.io/repos/github/ICanBoogie/HTTP/badge.svg?branch=7.0)](https://coveralls.io/r/ICanBoogie/HTTP?branch=7.0)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/http.svg)](https://packagist.org/packages/icanboogie/http)

The **icanboogie/http** package provides a foundation to handle HTTP requests, with representations
for requests, request files, responses, and headers.

The following example is an overview of request processing:

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
(new SimpleResponseSender())->send($response->finalize($request));
```



#### Installation

```shell
composer require icanboogie/http
```



## Documentation

- [Request](docs/Request.md): request creation, parameters, files, and context.
- [Response](docs/Response.md): response status, streaming, redirects, and file delivery.
- [Headers](docs/Headers.md): header fields manipulation.
- [Responders](docs/Responders.md): responders and their decorators.
- [Exceptions](docs/Exceptions.md): HTTP-related exceptions.



----------



## Continuous Integration

The project is continuously tested by [GitHub actions](https://github.com/ICanBoogie/HTTP/actions).

[![Test](https://github.com/ICanBoogie/HTTP/actions/workflows/test.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/HTTP/actions/workflows/test.yml)
[![Static Analysis](https://github.com/ICanBoogie/HTTP/actions/workflows/static-analysis.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/HTTP/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/ICanBoogie/HTTP/actions/workflows/code-style.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/HTTP/actions/workflows/code-style.yml)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you're expected to uphold this code.



## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.



## License

**icanboogie/http** is released under the [BSD-3-Clause](LICENSE).
