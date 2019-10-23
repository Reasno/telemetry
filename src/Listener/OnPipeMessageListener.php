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

namespace Hyperf\Telemetry\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\Event\PipeMessage;
use Hyperf\Telemetry\Adapter\RemoteProxy\Counter;
use Hyperf\Telemetry\Adapter\RemoteProxy\Gauge;
use Hyperf\Telemetry\Adapter\RemoteProxy\Histogram;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class OnPipeMessageListener implements ListenerInterface
{
    /**
     * @var TelemetryFactoryInterface
     */
    private $factory;

    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get(TelemetryFactoryInterface::class);    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            PipeMessage::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        if (property_exists($event, 'data') && $event instanceof PipeMessage) {
            $inner = $event->data;
            switch (true) {
                case $inner instanceof Counter:
                    $counter = $this->factory->makeCounter($inner->name, $inner->labelNames);
                    $counter->with(...$inner->labelValues)->add($inner->delta);
                    break;
                case $inner instanceof Gauge:
                    $gauge = $this->factory->makeGauge($inner->name, $inner->labelNames);
                    if (isset($inner->value)) {
                        $gauge->with(...$inner->labelValues)->set($inner->value);
                    } else {
                        $gauge->with(...$inner->labelValues)->add($inner->delta);
                    }
                    break;
                case $inner instanceof Histogram:
                    $histogram = $this->factory->makeHistogram($inner->name, $inner->labelNames);
                    $histogram->with(...$inner->labelValues)->observe($inner->value);
                    break;
                default:
                    // Nothing to do
                    break;
            }
        }
    }
}
