<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Telemetry\Middleware;

use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Timer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TelemetryMiddleware implements MiddlewareInterface
{
    /**
     * @var TelemetryFactoryInterface
     */
    private $factory;

    public function __construct()
    {
        // Must inject a short lived instance because the underlying class
        // is subject to the value of the configuration.
        $this->factory = make(TelemetryFactoryInterface::class);
    }

    /**
     * Process an incoming server request.
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $histogram = $this->factory->makeHistogram('request_latency', ['request_status', 'request_path', 'request_method']);
        $timer = new Timer($histogram);
        $response = $handler->handle($request);
        $timer
            ->with((string) $response->getStatusCode(), (string) $request->getRequestTarget(), $request->getMethod())
            ->observeDuration();
        return $response;
    }
}
