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

namespace Hyperf\Metric\Adapter\RemoteProxy;

use Hyperf\Metric\Contract\CounterInterface;
use Hyperf\Metric\Contract\GaugeInterface;
use Hyperf\Metric\Contract\HistogramInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Metric\Exception\RuntimeException;

class MetricFactory implements MetricFactoryInterface
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
        throw new RuntimeException('RemoteProxy adapter cannot be run directly in the metric process');
    }
}
