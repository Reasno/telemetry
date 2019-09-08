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

namespace Hyperf\Telemetry\Adapter\Prometheus;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;

class TelemetryFactory extends TelemetryFactoryInterface
{
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->registry = new CollectorRegistry(new InMemory());
    }
    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $this->registry,
            $this->config->get('namespace'),
            $name,
            'count '.$name,
            $labelNames
        );
    }
    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $this->registry,
            $this->config->get('namespace'),
            $name,
            'gauge '.$name,
            $labelNames
        );
    }
    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Gauge(
            $this->registry,
            $this->config->get('namespace'),
            $name,
            'measure '.$name,
            $labelNames
        );
    }
}
