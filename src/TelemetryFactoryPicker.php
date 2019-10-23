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
use Hyperf\Telemetry\Adapter\RemoteProxy\TelemetryFactory;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class TelemetryFactoryPicker
{
    public static $isWorker = false;

    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $useStandaloneProcess = $config->get('telemetry.use_standalone_process');
        // Return a proxy object for workers if user wants to use a dedicated telemetry process.
        if ($useStandaloneProcess && self::$isWorker) {
            return $container->get(TelemetryFactory::class);
        }

        $name = $config->get('telemetry.default');
        $dedicatedProcess = $config->get('telemetry.telemetry.use_standalone_process');
        $driver = $config->get("telemetry.telemetry.{$name}.driver");

        if (empty($driver)) {
            throw new InvalidArgumentException(
                sprintf('The telemetry config [%s] doesn\'t contain a valid driver.', $name)
            );
        }

        $factory = $container->get($driver);

        if (! ($factory instanceof TelemetryFactoryInterface)) {
            throw new InvalidArgumentException(
                sprintf('The driver %s is not a valid factory.', $driver)
            );
        }

        return $factory;
    }
}
