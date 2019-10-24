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

namespace Hyperf\Metric\Contract;

interface MetricFactoryInterface
{
    public function makeCounter($name, $labelNames): CounterInterface;

    public function makeGauge($name, $labelNames): GaugeInterface;

    public function makeHistogram($name, $labelNames): HistogramInterface;

    public function handle(): void;
}
