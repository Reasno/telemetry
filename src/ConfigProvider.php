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

use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                TelemetryFactoryInterface::class => TelemetryFactoryPicker::class,
                Adapter::class => InMemory::class,
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for telemetry component.',
                    'source' => __DIR__ . '/../publish/telemetry.php',
                    'destination' => BASE_PATH . '/config/autoload/telemetry.php',
                ],
            ],
        ];
    }
}
