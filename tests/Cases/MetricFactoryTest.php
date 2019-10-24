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

namespace HyperfTest\Cases;

use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Metric\Adapter\Prometheus\Constants;
use Hyperf\Metric\Adapter\Prometheus\MetricFactory as PrometheusFactory;
use Hyperf\Metric\Adapter\RemoteProxy\MetricFactory as RemoteFactory;
use Hyperf\Metric\Adapter\StatsD\MetricFactory as StatsDFactory;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Metric\Exception\RuntimeException;
use Hyperf\Metric\Listener\OnWorkerStartListener;
use Hyperf\Metric\MetricFactoryPicker;
use Mockery;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
class MetricFactoryTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testPrometheusThrows()
    {
        $config = new Config([
            'metric' => [
                'default' => 'prometheus',
                'use_standalone_process' => false,
                'metric' => [
                    'prometheus' => [
                        'driver' => PrometheusFactory::class,
                        'mode' => Constants::SCRAPE_MODE
                    ],
                ]
            ],
        ]);
        $r = Mockery::mock(CollectorRegistry::class);
        $c = Mockery::mock(ClientFactory::class);
        $this->expectException(RuntimeException::class);
        $p = new PrometheusFactory($config, $r, $c);
    }
}
