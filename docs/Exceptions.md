# Exceptions

The HTTP package defines the following exceptions:

* [ClientError][]: thrown when a client error occurs.
    * [NotFound][]: thrown when a resource is not found. For instance, this exception is
    thrown by `Responder\DelegateToProvider` when no responder matches the request.
    * [AuthenticationRequired][]: thrown when the authentication of the client is required. Implements [SecurityError][].
    * [AuthenticationFailed][]: thrown when the authentication of the client failed. Implements [SecurityError][].
    * [PermissionRequired][]: thrown when the client lacks a required permission. Implements [SecurityError][].
    * [MethodNotAllowed][]: thrown when an HTTP method is not supported.
* [ServerError][]: throw when a server error occurs.
    * [ServiceUnavailable][]: thrown when a server is currently unavailable
    (because it is overloaded or down for maintenance).
* [ForceRedirect][]: thrown when a redirect is absolutely required.
* [StatusCodeNotValid][]: thrown when an HTTP status code is not valid.

Exceptions defined by the package implement the `ICanBoogie\HTTP\Exception` interface.
Using this interface, one can easily catch HTTP-related exceptions:

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



[AuthenticationFailed]:                ../lib/AuthenticationFailed.php
[AuthenticationRequired]:        ../lib/AuthenticationRequired.php
[ClientError]:                   ../lib/ClientError.php
[ForceRedirect]:                 ../lib/ForceRedirect.php
[MethodNotAllowed]:              ../lib/MethodNotAllowed.php
[NotFound]:                      ../lib/NotFound.php
[PermissionRequired]:            ../lib/PermissionRequired.php
[SecurityError]:                 ../lib/SecurityError.php
[ServerError]:                   ../lib/ServerError.php
[ServiceUnavailable]:            ../lib/ServiceUnavailable.php
[StatusCodeNotValid]:            ../lib/StatusCodeNotValid.php
