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

use Hyperf\Process\ProcessCollector;
use Hyperf\Telemetry\Contract\CounterInterface;

class Counter implements CounterInterface
{
    /**
     * @var string
     */
    protected const TARGET_PROCESS_NAME = 'telemetry';

    /**
     * @var string
     */
    public $name;

    /**
     * @var string[];
     */
    public $labelNames = [];

    /**
     * @var string[]
     */
    public $labelValues = [];

    /**
     * @var float
     */
    public $delta;

    public function __construct(string $name, array $labelNames)
    {
        $this->name = $name;
        $this->labelNames = $labelNames;
    }

    public function with(string ...$labelValues): CounterInterface
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function add(float $delta)
    {
        $this->delta = $delta;
        $process = ProcessCollector::get(static::TARGET_PROCESS_NAME)[0];
        $process->exportSocket()->send(serialize($this));
    }
}
