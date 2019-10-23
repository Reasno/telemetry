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

namespace Hyperf\Telemetry\Adapter\RemoteProxy;

use Hyperf\Telemetry\Contract\CounterInterface;
use Hyperf\Telemetry\Contract\GaugeInterface;
use Hyperf\Telemetry\Contract\HistogramInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Exception\RuntimeException;

class TelemetryFactory implements TelemetryFactoryInterface
{
    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $name,
            $labelNames
        );
    }

    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $name,
            $labelNames
        );
    }

    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Histogram(
            $name,
            $labelNames
        );
    }

    public function handle(): void
    {
        throw new RuntimeException('RemoteProxy adapter cannot be run directly in the telemetry process');
    }
}
