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
namespace Hyperf\Telemetry;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine\Http\Server;

/**
 * Class Process.
 * @Process
 */
class TelemetryProcess extends AbstractProcess
{
    public $name = 'telemetry';
    public $nums = 1;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container, TelemetryFactoryInterface $factory)
    {
        $process = parent::__construct($container);
        $this->factory = $factory;
    }
    /**
     * The logical of process will place in here.
     */
    public function handle(): void
    {   
        $this->factory->handle();
    }
}
