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

namespace Hyperf\Metric\Adapter\Prometheus;

use Hyperf\Metric\Contract\GaugeInterface;

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
    protected $labelValues = [];

    public function __construct(\Prometheus\CollectorRegistry $registry, string $namespace, string $name, string $help, array $labelNames)
    {
        $this->registry = $registry;
        $this->gauge = $registry->getOrRegisterGauge($namespace, $name, $help, $labelNames);
    }

    public function with(string ...$labelValues): GaugeInterface
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
        $this->gauge->incBy($delta, $this->labelValues);
    }
}
