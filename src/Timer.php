<?php

declare(strict_types=1);

namespace Hyperf\Telemetry;

use Hyperf\Telemetry\Contract\HistogramInterface;

class Timer
{
    /**
     * @var HistogramInterface
     */
    protected $histogram;

    /**
     * @var float
     */
    protected $time;

    public function __construct(HistogramInterface $histogram)
    {
        $this->histogram = $histogram;
        $this->time = microtime(true);
    }

    public function observeDuration()
    {
        $d = (float) microtime(true) - $this->time;
        if ($d < 0) {
            $d = (float)0;
        }
        $this->histogram->observe($d);
    }

    public function with(string ...$labelValues): self
    {
        $this->histogram->with(...$labelValues);
        return $this;
    }
}
