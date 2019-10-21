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
use Prometheus\Storage\InMemory;
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
     * GuzzleClientFactory
     */
    private $guzzleClientFactory;

    public function __construct(ConfigInterface $config, CollectorRegistry $registry, GuzzleClientFactory $guzzleClientFactory)
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->guzzleClientFactory = $guzzleClientFactory;
    }
    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $this->registry,
            $this->getNamespace(),
            $name,
            'count '.$name,
            $labelNames
        );
    }
    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $this->registry,
            $this->getNamespace(),
            $name,
            'gauge '.$name,
            $labelNames
        );
    }
    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Histogram(
            $this->registry,
            $this->getNamespace(),
            $name,
            'measure '.$name,
            $labelNames
        );
    }

    public function handle(): void
    {
        $name = $this->config->get('telemetry.default');
        if ($this->config->get("telemetry.telemetry.{$name}.mode") == Constants::PULL_MODE) {
            $host = $this->config->get("telemetry.telemetry.{$name}.scape_host");
            $port = $this->config->get("telemetry.telemetry.{$name}.scape_port");
            $path = $this->config->get("telemetry.telemetry.{$name}.scape_path");
            $renderer = new RenderTextFormat();
            go(function () use ($renderer, $host, $port, $path) {
                $server = new Server($host, $port, false);
                $server->handle($path, function ($request, $response) use ($renderer) {
                    $response->end($renderer->render($this->registry->getMetricFamilySamples()));
                });
                $server->start();
            });
        }

        // Block handle from returning.
        while(True){
            Coroutine::sleep(5);
            if ($this->config->get("telemetry.telemetry.{$name}.mode") == Constants::PUSH_MODE) {
                $host = $this->config->get("telemetry.telemetry.{$name}.push_host");
                $port = $this->config->get("telemetry.telemetry.{$name}.push_port");
                $this->doRequest("$host:$port", $this->getNamespace(), null, 'put');
            }
        }
    }

    private function getNamespace(): string {
        $name = $this->config->get('telemetry.default');
        return $this->config->get("telemetry.telemetry.{$name}.namespace");
    }

    /**
     * @param CollectorRegistry $collectorRegistry
     * @param $job
     * @param $groupingKey
     * @param $method
     */
    private function doRequest($address, $job, $groupingKey, $method)
    {
        $url = "http://" . $address . "/metrics/job/" . $job;
        if (!empty($groupingKey)) {
            foreach ($groupingKey as $label => $value) {
                $url .= "/" . $label . "/" . $value;
            }
        }
        $client = $this->guzzleClientFactory->create();
        $requestOptions = array(
            'headers' => array(
                'Content-Type' => RenderTextFormat::MIME_TYPE
            ),
            'connect_timeout' => 10,
            'timeout' => 20,
        );
        if ($method != 'delete') {
            $renderer = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($this->registry->getMetricFamilySamples());
        }
        $response = $client->request($method, $url, $requestOptions);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200 && $statusCode != 202) {
            $msg = "Unexpected status code " . $statusCode . " received from pushgateway " . $address . ": " . $response->getBody();
            throw new RuntimeException($msg);
        }
    }


}
