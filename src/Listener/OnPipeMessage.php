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

namespace Hyperf\ConfigEtcd\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\Event\PipeMessage;
use Hyperf\Telemetry\Adapter\RemoteProxy\Counter;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;

/**
 * @Listener
 */
class OnPipeMessage implements ListenerInterface
{
    /**
     * @var TelemetryFactoryInterface
     */
    private $factory;

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;


    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get(TelemetryFactoryInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

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
        if (property_exists($event, 'data') && $event->data instanceof PipeMessage) {
            /** @var PipeMessage $data */
            $data = $event->data;

            $inner = $data->data;

            if ($inner instanceof Counter) {
                $counter = $this->factory->makeCounter($inner->name, $inner->labelNames);
                $counter->with(...$inner->labelValues)->add($inner->delta);
                return;
            }

            if ($inner instanceof Gauge) {
                $gauge = $this->factory->makeGauge($inner->name, $inner->labelNames);
                if ($inner->value) {
                    $gauge->with(...$inner->labelValues)->set($inner->value);
                } else {
                    $gauge->with(...$inner->labelValues)->add($inner->delta);
                }
                return;
            }

            if ($inner instanceof Histogram) {
                $histogram = $this->factory->makeGauge($inner->name, $inner->labelNames);
                $histogram->with(...$inner->labelValues)->observe($inner->delta);
                return;
            }
        }
    }
}
