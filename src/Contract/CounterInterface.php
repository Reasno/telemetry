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

/**
 * Counter describes a metric that accumulates values monotonically.
 * An example of a counter is the number of received HTTP requests.
 */
interface CounterInterface
{
    public function with(string ...$labelValues): self;

    public function add(float $delta);
}
