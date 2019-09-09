<?php

declare(strict_types=1);

namespace Hyperf\Telemetry\Adapter\RemoteProxy;

use Hyperf\Process\ProcessCollector;
use Hyperf\Telemetry\Contract\GaugeInterface;

class Gauge implements GaugeInterface
{
    /**
     * @var string
     */
    protected const TARGET_PROCESS_NAME = "telemetry";
    /**
     * @var string
     */
    public $name;

    /**
     * @var string[];
     */
    public $labelNames;

    /**
     * @var string[]
     */
    public $labelValues;

    /**
     * @var float
     */
    public $delta;

    /**
     * @var float
     */
    public $value;

    public function __construct(string $name, array $labelNames)
    {
        $this->name = $name;
        $this->labelNames = $labelNames;
    }

    public function with(string ...$labelValues): self
    {
        $this->labelValues = $labelValues;
        return $this;
    }

    public function set(float $value)
    {
        $this->value = $value;
        $this->delta = null;
        $process = ProcessCollector::get(static::TARGET_PROCESS_NAME)[0];
        $process->exportSocket()->send(serialize($this));
    }

    public function add(float $delta)
    {
        $this->delta = $delta;
        $this->value = null;
        $process = ProcessCollector::get(static::TARGET_PROCESS_NAME)[0];
        $process->exportSocket()->send(serialize($this));
    }
}
