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

namespace Hyperf\Metric;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Metric\Event\MetricFactoryReady;
use Hyperf\Metric\MetricFactoryPicker;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class Process.
 * @Process
 */
class MetricProcess extends AbstractProcess
{
    public $name = 'metric';

    public $nums = 1;

    /**
     * @var MetricFactoryInterface
     */
    protected $factory;

    public function isEnable(): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get('metric.use_standalone_process') ?? false;
    }

    /**
     * The logical of process will place in here.
     */
    public function handle(): void
    {
        MetricFactoryPicker::$inMetricProcess = true;
        $this->factory = make(MetricFactoryInterface::class);
        $this
            ->container
            ->get(EventDispatcherInterface::class)
            ->dispatch(new MetricFactoryReady($this->factory));
        $this
            ->factory
            ->handle();
    }
}
