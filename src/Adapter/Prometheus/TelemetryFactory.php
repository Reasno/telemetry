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

namespace Hyperf\Telemetry\Adapter\Prometheus;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory as GuzzleClientFactory;
use Hyperf\Telemetry\Contract\CounterInterface;
use Hyperf\Telemetry\Contract\GaugeInterface;
use Hyperf\Telemetry\Contract\HistogramInterface;
use Hyperf\Telemetry\Contract\TelemetryFactoryInterface;
use Hyperf\Telemetry\Exception\RuntimeException;
use Hyperf\Utils\Coroutine;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Swoole\Coroutine\Http\Server;

class TelemetryFactory implements TelemetryFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CollectorRegistry
     */
    private $registry;

    /**
     * GuzzleClientFactory.
     */
    private $guzzleClientFactory;

    public function __construct(ConfigInterface $config, CollectorRegistry $registry, GuzzleClientFactory $guzzleClientFactory)
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->guzzleClientFactory = $guzzleClientFactory;
        $this->guardConfig();
    }

    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $this->registry,
            $this->getNamespace(),
            $name,
            'count ' . $name,
            $labelNames
        );
    }

    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $this->registry,
            $this->getNamespace(),
            $name,
            'gauge ' . $name,
            $labelNames
        );
    }

    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Histogram(
            $this->registry,
            $this->getNamespace(),
            $name,
            'measure ' . $name,
            $labelNames
        );
    }

    public function handle(): void
    {
        $name = $this->config->get('telemetry.default');
        if ($this->config->get("telemetry.telemetry.{$name}.mode") == Constants::PULL_MODE) {
            $host = $this->config->get("telemetry.telemetry.{$name}.scrape_host");
            $port = $this->config->get("telemetry.telemetry.{$name}.scrape_port");
            $path = $this->config->get("telemetry.telemetry.{$name}.scrape_path");
            $renderer = new RenderTextFormat();
            go(function () use ($renderer, $host, $port, $path) {
                $server = new Server($host, (int) $port, false);
                $server->handle($path, function ($request, $response) use ($renderer) {
                    $response->header('Content-Type', 'text/plain');
                    $response->header('X-Content-Type-Options', 'nosniff');
                    $response->end($renderer->render($this->registry->getMetricFamilySamples()));
                });
                $server->start();
            });
        }

        // Block handle from returning.
        while (true) {
            if ($this->config->get("telemetry.telemetry.{$name}.mode") == Constants::PUSH_MODE) {
                $inteval = $this->config->get("telemetry.telemetry.{$name}.push_inteval");
                $host = $this->config->get("telemetry.telemetry.{$name}.push_host");
                $port = $this->config->get("telemetry.telemetry.{$name}.push_port");
                $this->doRequest("{$host}:{$port}", $this->getNamespace(), null, 'put');
                Coroutine::sleep($inteval);
            } else {
                Coroutine::sleep(100);
            }
        }
    }

    private function getNamespace(): string
    {
        $name = $this->config->get('telemetry.default');
        return $this->config->get("telemetry.telemetry.{$name}.namespace");
    }

    private function guardConfig()
    {
        $name = $this->config->get('telemetry.default');
        if ($this->config->get("telemetry.telemetry.{$name}.mode") == Constants::PULL_MODE &&
            $this->config->get('telemetry.use_standalone_process') == false) {
            throw new RuntimeException(
                "Prometheus in pull mode must be used in conjunction with standalone process. \n Set telemetry.use_standalone_process to true to avoid this error."
            );
        }
    }

    /**
     * @param CollectorRegistry $collectorRegistry
     * @param $job
     * @param $groupingKey
     * @param $method
     * @param mixed $address
     */
    private function doRequest($address, $job, $groupingKey, $method)
    {
        $url = 'http://' . $address . '/metrics/job/' . $job;
        if (! empty($groupingKey)) {
            foreach ($groupingKey as $label => $value) {
                $url .= '/' . $label . '/' . $value;
            }
        }
        $client = $this->guzzleClientFactory->create();
        $requestOptions = [
            'headers' => [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ],
            'connect_timeout' => 10,
            'timeout' => 20,
        ];
        if ($method != 'delete') {
            $renderer = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($this->registry->getMetricFamilySamples());
        }
        $response = $client->request($method, $url, $requestOptions);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200 && $statusCode != 202) {
            $msg = 'Unexpected status code ' . $statusCode . ' received from pushgateway ' . $address . ': ' . $response->getBody();
            throw new RuntimeException($msg);
        }
    }
}
