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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Telemetry\Contract\GaugeInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\TelemetryFactoryPicker;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;

/**
 * @Listener
 */
class OnWorkerStartListener implements ListenerInterface
{
    /**
     * @var TelemetryFactoryInterface
     */
    protected $factory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        TelemetryFactoryPicker::$isWorker = true;
        $workerId = $event->workerId;
        if ( $this->shouldFireTelemetryRegistryReadyEvent($workerId) ){
            $this->eventDispatcher->dispatch(new TelemetryRegistryReady());
        }
        $this->factory = make(TelemetryFactoryInterface::class);

        $metrics = $this->factoryMetrics(
            $workerId,
            'memory_usage',
            'memory_peak_usage',
            'worker_request_count',
            'worker_dispatch_count'
        );
        $server = make(Server::class);

        Timer::tick(5000, function () use ($metrics, $server) {
            $serverStats = $server->stats();
            $metrics['memory_usage']->set(\memory_get_usage());
            $metrics['memory_peak_usage']->set(\memory_get_peak_usage());
            $metrics['worker_request_count']->set($serverStats['worker_request_count']);
            $metrics['worker_dispatch_count']->set($serverStats['worker_dispatch_count']);
        });
    }

    /**
     * Create an array of gauges.
     * @param int $workerId
     * @param string[] $names
     * @return GaugeInterface[]
     */
    private function factoryMetrics(int $workerId, string ...$names): array
    {
        $out = [];
        foreach ($names as $name) {
            $out[$name] = $this
                ->factory
                ->makeGauge($name, ['worker_id'])
                ->with((string) $workerId);
        }
        return $out;
    }

    private function shouldFireTelemetryRegistryReadyEvent(int $workerId): bool {
        return (!$this->config->get('telemetry.use_standalone_processs'))
            && $workerId == 0;
    }
}
