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

namespace Hyperf\Telemetry\Adapter\StatsD;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection\UdpSocket;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Telemetry\Adapter\Statsd\Gauge;
use Hyperf\Telemetry\Adapter\Statsd\Histogram;
use Hyperf\Telemetry\Contract\CounterInterface;
use Hyperf\Telemetry\Contract\GaugeInterface;
use Hyperf\Telemetry\Contract\HistogramInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Utils\Coroutine;

class TelemetryFactory implements TelemetryFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CollectorRegistry
     */
    private $client;

    /**
     * GuzzleClientFactory.
     */
    private $guzzleClientFactory;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->client = make(Client::class, [$this->getConnection(), $this->getNamespace(), $this->getSampleRate()]);
    }

    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $this->client,
            $name,
            $this->getSampleRate(),
            $labelNames
        );
    }

    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $this->client,
            $name,
            $this->getSampleRate(),
            $labelNames
        );
    }

    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Histogram(
            $this->client,
            $name,
            $this->getSampleRate(),
            $labelNames
        );
    }

    public function handle(): void
    {
        $name = $this->config->get('telemetry.default');
        $batchInteval = $this->config->get("telemetry.telemetry.{$name}.batch_inteval") ?? 5;
        $batchEnabled = $this->config->get("telemetry.telemetry.{$name}.batch") == true;
        // Block handle from returning.
        do {
            if ($batchEnabled) {
                $this->client->startBatch();
                Coroutine::sleep((int) $batchInteval);
                $this->client->endBatch();
            } else {
                Coroutine::sleep((int) $batchInteval);
            }
        } while (true);
    }

    protected function getConnection(): string
    {
        $name = $this->config->get('telemetry.default');
        $host = $this->config->get("telemetry.telemetry.{$name}.udp_host");
        $port = $this->config->get("telemetry.telemetry.{$name}.udp_port");
        return new UdpSocket($host, (int) $port);
    }

    protected function getNamespace(): string
    {
        $name = $this->config->get('telemetry.default');
        return $this->config->get("telemetry.telemetry.{$name}.namespace");
    }

    protected function getSampleRate(): float
    {
        $name = $this->config->get('telemetry.default');
        return $this->config->get("telemetry.telemetry.{$name}.sample_rate");
    }
}
