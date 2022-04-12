<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Exception;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Event class for the `Exception:rescue` event type.
 *
 * Third parties may use this event to provide a response for the exception.
 *
 * @property \Throwable $exception
 * @property-read Request $request
 * @property Response $response
 */
class RescueEvent extends Event
{
    public const TYPE = 'rescue';

    /**
     * Reference to the response.
     *
     * @var Response|null
     */
    private $response;

    protected function get_response(): ?Response
    {
        return $this->response;
    }

    protected function set_response(?Response $response)
    {
        $this->response = $response;
    }

    /**
     * @var \Throwable
     */
    private $exception;

    protected function get_exception(): \Throwable
    {
        return $this->exception;
    }

    protected function set_exception(\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @var Request
     */
    private $request;

    protected function get_request(): Request
    {
        return $this->request;
    }

    /**
     * The event is constructed with the type `rescue`.
     *
     * @param \Throwable $target
     * @param Request $request The request.
     * @param Response|null $response Reference to the response.
     */
    public function __construct(\Throwable &$target, Request $request, Response &$response = null)
    {
        $this->response = &$response;
        $this->exception = &$target;
        $this->request = $request;

        parent::__construct($target, self::TYPE);
    }
}
