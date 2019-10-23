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
use Psr\Container\ContainerInterface;

/**
 * Class Process.
 * @Process
 */
class TelemetryProcess extends AbstractProcess
{
    public $name = 'telemetry';

    public $nums = 1;

    /**
     * @var TelemetryFactoryInterface
     */
    protected $factory;

    public function __construct(ContainerInterface $container, TelemetryFactoryInterface $factory)
    {
        parent::__construct($container);
        $this->factory = $factory;
    }

    public function isEnable(): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get('telemetry.use_standalone_process') ?? false;
    }

    /**
     * The logical of process will place in here.
     */
    public function handle(): void
    {
        $this->factory->handle();
    }
}
