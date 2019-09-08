<?php

declare(strict_types=1);

namespace Hyperf\Telemetry;

use Hyperf\Telemetry\Adapter\Prometheus;
use Hyperf\Telemetry\Contract\HistogramInterface;

class Histogram implements HistogramInterface
{
    /**
     * @var \Prometheus\CollectorRegistry
     */
    protected $registry;

    /**
     * @var \Prometheus\Histogram
     */
    protected $histogram;

    /**
     * @var string[]
     */
    protected $labelValues;


    public function __construct(\Prometheus\CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        $this->registry = $registry;
        $this->histogram = $registry->getOrRegisterHistogram($name, $help, $type, $labelNames);
    }

    public function with(string ...$labelValues): self
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function observe(float $value)
    {
        $this->histogram->observe($value, $this->labelValues);
    }
}
