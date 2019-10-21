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
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class TelemetryFactoryPicker
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $name = $this->config->get('telemetry.default');
        $driver = $this->config->get("telemetry.telemetry.{$name}.driver");

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
