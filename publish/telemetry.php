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

use Hyperf\Telemetry\Adapter\Prometheus\Constants;

return [
    'default' => env('TELEMETRY_DRIVER', 'prometheus'),
    'enable' => [
        'guzzle' => env('TRACER_ENABLE_GUZZLE', false),
        'redis' => env('TRACER_ENABLE_REDIS', false),
        'db' => env('TRACER_ENABLE_DB', false),
        'method' => env('TRACER_ENABLE_METHOD', false),
    ],
    'telemetry' => [
        'prometheus' => [
            'driver' => Hyperf\Telemetry\Adapter\Prometheus\TelemetryFactory::class,
            'mode' => Constants::PULL_MODE,
            'namespace' => env('APP_NAME', 'skeleton'),
            'scrape_host' => env('PROMETHEUS_SCRAPE_HOST', '0.0.0.0'),
            'scrape_port' => env('PROMETHEUS_SCRAPE_PORT', '9502'),
            'scrape_path' => env('PROMETHEUS_SCRAPE_PATH', '/metrics'),
        ],
    ],
];
