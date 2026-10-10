<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Closure;

use function header;
use function header_remove;
use function headers_sent;
use function trigger_error;

/**
 * Sends a response using PHP's {@see header()} function and the output.
 *
 * The `Pragma` and `X-Powered-By` header fields that PHP or the web server might have defined are
 * removed.
 *
 * The class is left open for overloading.
 */
class SimpleResponseSender implements ResponseSender
{
    public function send(FinalResponse $response): void
    {
        $this->send_headers($response);

        $body = $response->body;

        if ($body === null) {
            return;
        }

        $this->send_body($response, $body);
    }

    // @codeCoverageIgnoreStart
    protected function send_headers(FinalResponse $response): void
    {
        if (headers_sent($file, $line)) {
            trigger_error(
                "Cannot modify header information because it was already sent. Output started at $file:$line",
            );

            return;
        }

        header_remove('Pragma');
        header_remove('X-Powered-By');

        header("HTTP/$response->version $response->status");

        foreach ($response->headers->fields() as $field => $value) {
            header("$field: $value");
        }
    }
    // @codeCoverageIgnoreEnd

    protected function send_body(FinalResponse $response, Closure|string $body): void
    {
        if ($body instanceof Closure) {
            $body($response);

            return;
        }

        echo $body;
    }
}
