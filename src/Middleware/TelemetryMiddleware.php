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

namespace Hyperf\Tracer\Middleware;

use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Timer;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Utils\Coroutine;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TelemetryMiddeware implements MiddlewareInterface
{

    /**
     * @var TelemetryFactoryInterface
     */
    private $factory;

    public function __construct(TelemetryFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Process an incoming server request.
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $counter = $factory->makeCounter('request_count', ['request_status','request_path', 'request_method']);
        $histogram = $factory->makeHistogram('request_latency', ['request_status','request_path', 'request_method']);
        $timer = new Timer($histogram);
        $response = $handler->handle($request);
        $timer
            ->with($response->getStatusCode(), (string)$request->getUri(), $request->getMethod())
            ->observeDuration();
        $counter
            ->with($response->getStatusCode(), (string)$request->getUri(), $request->getMethod())
            ->add(1);
        return $response;
    }
}
