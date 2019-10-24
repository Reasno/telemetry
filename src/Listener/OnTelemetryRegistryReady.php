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

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Telemetry\Contract\GaugeInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Event\TelemetryRegistryReady;
use Hyperf\Telemetry\TelemetryFactoryPicker;
use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;

/**
 * @Listener
 */
class OnTelemetryRegistryReady implements ListenerInterface
{
    /**
     * @var TelemetryFactoryInterface
     */
    protected $factory;

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            TelemetryRegistryReady::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        $this->factory = make(TelemetryFactoryInterface::class);
        $metrics = $this->factoryMetrics(
            'sys_load',
            'event_num',
            'signal_listener_num',
            'aio_task_num',
            'aio_worker_num',
            'c_stack_size',
            'coroutine_num',
            'coroutine_peak_num',
            'coroutine_last_cid',
            'start_time',
            'connection_num',
            'accept_count',
            'close_count',
            'worker_num',
            'idle_worker_num',
            'tasking_num',''
            'request_count',
        );
        $server = make(Server::class);

        Timer::tick(5000, function () use ($metrics, $server) {
            $serverStats = $server->stats();
            $coroutineStats = Coroutine::stats();
            $metrics['memory_usage']->set(\memory_get_usage());
            $metrics['memory_peak_usage']->set(\memory_get_peak_usage());
            $load = sys_getloadavg();
            $metrics['sys_load']->set($load[0] / \swoole_cpu_num());
            $metrics['event_num']->set($coroutineStats['event_num']);
            $metrics['signal_listener_num']->set($coroutineStats['signal_listener_num']);
            $metrics['aio_task_num']->set($coroutineStats['aio_task_num']);
            $metrics['aio_worker_num']->set($coroutineStats['aio_worker_num']);
            $metrics['c_stack_size']->set($coroutineStats['c_stack_size']);
            $metrics['coroutine_num']->set($coroutineStats['coroutine_num']);
            $metrics['coroutine_peak_num']->set($coroutineStats['coroutine_peak_num']);
            $metrics['coroutine_last_cid']->set($coroutineStats['coroutine_last_cid']);
            $metrics['start_time']->set($serverStats['start_time']);
            $metrics['connection_num']->set($serverStats['connection_num']);
            $metrics['accept_count']->set($serverStats['accept_count']);
            $metrics['close_count']->set($serverStats['close_count']);
            $metrics['worker_num']->set($serverStats['worker_num']);
            $metrics['idle_worker_num']->set($serverStats['idle_worker_num']);
            $metrics['tasking_num']->set($serverStats['tasking_num']);
            $metrics['request_count']->set($serverStats['request_count']);
        });
    }

    /**
     * Create an array of gauges.
     * @param string[] $names
     * @return GaugeInterface[]
     */
    private function factoryMetrics(string ...$names): array
    {
        $out = [];
        foreach ($names as $name) {
            $out[$name] = $this
                ->factory
                ->makeGauge($name, []);
        }
        return $out;
    }
}
