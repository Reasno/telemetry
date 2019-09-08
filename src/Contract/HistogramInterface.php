<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Contract;

/**
 * Histogram describes a metric that takes repeated observations of the same
 * kind of thing, and produces a statistical summary of those observations,
 * typically expressed as quantiles or buckets. An example of a histogram is
 * HTTP request latencies.
 */
interface HistogramInterface
{
    public function with(string ...$labelValues) : self;
    public function observe(float $delta);
}
