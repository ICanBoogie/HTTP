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

$responder = new Responder\DelegateToProvider($provider);
$response = $responder->respond($request);
```

## Rescuing exceptions

[WithRecovery][] decorates another responder to provide an exception recovery mechanism.

The exception thrown by the responder is caught and a [RecoverEvent][] is emitted. Third parties can
use that event to provide a response or replace the exception.

The following example demonstrate how to decorate a responder:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Responder $responder */

$responder_with_recovery = new Responder\WithRecovery($responder);
```

The following example demonstrate how to recover [NotFound][] exceptions:

```php
<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\EventCollection;

/* @var EventCollection $events */

$events->attach(function (RecoverEvent $event, NotFound $target) {

    $event->response = new Response(
        "These aren't the droids you're looking for.",
        ResponseStatus::STATUS_NOT_FOUND
    );

});

$responder_with_recovery = new Responder\WithRecovery($responder);
```

Alternatively you can provide another exception to throw instead:

```php
<?php

namespace ICanBoogie\HTTP;

use ICanBoogie\EventCollection;

/* @var EventCollection $events */

$events->attach(function (RecoverEvent $event, NotFound $target) {

    $event->exception = new AnotherException();

});

$responder_with_recovery = new Responder\WithRecovery($responder);
```


## Events around respond

[WithEvents][] decorates another responder to emit events around the `respond()` method.

- [BeforeRespondEvent][] is emitted before the `respond()` method. Listeners can use the event to
  alter the request or provide a response. If a response is provided the `respond()` method is *not*
  invoked.
- [RespondEvent][] is emitted after the `respond()` method. Listeners can use the event to alter the
  response.

The following example demonstrate how to decorate a responder:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var Responder $responder */

$responder_with_events = new Responder\WithEvents($responder);
```

The following example demonstrate how to attach listeners:

```php
<?php

namespace ICanBoogie\HTTP;

/* @var \ICanBoogie\EventCollection $events */

$events->attach(function (BeforeRespondEvent $event) {
    // …
});

$events->attach(function (RespondEvent $event) {
    // …
});
```




[ICanBoogie/Routing]: https://github.com/ICanBoogie/Routing
[WithRecovery]: ../lib/Responder/WithRecovery.php
[RecoverEvent]: ../lib/RecoverEvent.php
[NotFound]: ../lib/NotFound.php
