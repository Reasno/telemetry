<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Prometheus;

use Hyperf\Telemetry\Contract\GaugeInterface;

class Gauge implements GaugeInterface
{
    /**
     * @var \Prometheus\CollectorRegistry
     */
    protected $registry;

    /**
     * @var \Prometheus\Gauge
     */
    protected $gauge;

    /**
     * @var string[]
     */
    protected $labelValues;


    public function __construct(\Prometheus\CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        $this->registry = $registry;
        $this->gauge = $registry->getOrRegisterGauge($name, $help, $type, $labelNames);
    }

    public function with(string ...$labelValues): self
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function set(float $value)
    {
        $this->gauge->set($value, $this->labelValues);
        return $this;
    }

    public function add(float $delta)
    {
        $this->counter->incBy($delta, $this->labelValues);
    }
}
