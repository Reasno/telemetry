<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Prometheus;

use Hyperf\Telemetry\Contract\CounterInterface;

class Counter implements CounterInterface
{
    /**
     * @var \Prometheus\CollectorRegistry
     */
    protected $registry;

    /**
     * @var \Prometheus\Counter
     */
    protected $counter;

    /**
     * @var string[]
     */
    protected $labelValues;


    public function __construct(\Prometheus\CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        $this->registry = $registry;
        $this->counter = $registry->getOrRegisterCounter($name, $help, $type, $labelNames);
    }

    public function with(string ...$labelValues): self
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function add(float $delta)
    {
        $processes = ProcessCollector::get(static::TARGET_PROCESS_NAME);
        $process->exportSocket()->send(serialize(new ReporterMessage(
            $this->options,
            $spans
        )));
        $this->counter->incBy($delta, $this->labelValues);
    }
}
