<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Contract;

/**
 * Gauge describes a metric that takes specific values over time.
 * An example of a gauge is the current depth of a job queue.
 */
interface GaugeInterface
{
    public function with(string ...$labelValues) : self;
    public function set(float $value);
    public function add(float $delta);
}
