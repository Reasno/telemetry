<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Contract;

/**
 * Counter describes a metric that accumulates values monotonically.
 * An example of a counter is the number of received HTTP requests.
 */
interface CounterInterface
{
    public function with(string ...$labelValues) : self;
    public function add(float $delta);
}
