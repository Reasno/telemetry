<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Adapter\Prometheus;

use Hyperf\Telemetry\Contract\CounterInterface;
use Prometheus\CollectorRegistry;

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


    public function __construct(CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        $this->registry = $registry;
        $this->counter = $registry->getOrRegisterCounter($namespace, $name, $help, $labelNames);
    }

    public function with(string ...$labelValues): CounterInterface
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function add(float $delta)
    {
        $this->counter->incBy($delta, $this->labelValues);
    }
}
