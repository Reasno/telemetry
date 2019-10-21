<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Adapter\Prometheus;

use Hyperf\Telemetry\Contract\HistogramInterface;
use Prometheus\CollectorRegistry;

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


    public function __construct(CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        var_dump($labelNames);
        $this->registry = $registry;
        $this->histogram = $registry->getOrRegisterHistogram($namespace, $name, $help, $labelNames);
    }

    public function with(string ...$labelValues): HistogramInterface
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function observe(float $value)
    {
        $this->histogram->observe($value, $this->labelValues);
    }
}
