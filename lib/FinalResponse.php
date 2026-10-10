<?php

declare(strict_types=1);

namespace ICanBoogie\HTTP;

use Closure;

use function ob_get_clean;
use function ob_start;

/**
 * The response as it is sent to the client.
 *
 * Instances are produced by {@see Response::finalize()}, which doesn't change the response, and
 * are what a {@see ResponseSender} sends.
 */
final readonly class FinalResponse
{
    /**
     * @param Closure|string|null $body A closure is invoked with the {@see FinalResponse} to send
     * the body. It writes the body to the output.
     */
    public function __construct(
        public string $version,
        public Status $status,
        public Headers $headers,
        public Closure|string|null $body,
    ) {
    }

    /**
     * Renders the response as an HTTP string.
     */
    public function __toString(): string
    {
        $body = $this->body;

        if ($body instanceof Closure) {
            ob_start();

            try {
                $body($this);
            } finally {
                $body = ob_get_clean();
            }
        }

        return "HTTP/$this->version $this->status\r\n"
            . $this->headers
            . "\r\n"
            . $body;
    }
}
