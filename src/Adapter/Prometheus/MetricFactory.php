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

namespace Hyperf\Metric\Adapter\Prometheus;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory as GuzzleClientFactory;
use Hyperf\Metric\Contract\CounterInterface;
use Hyperf\Metric\Contract\GaugeInterface;
use Hyperf\Metric\Contract\HistogramInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Metric\Exception\InvalidArgumentException;
use Hyperf\Metric\Exception\RuntimeException;
use Hyperf\Utils\Coroutine;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Swoole\Coroutine\Http\Server;

class MetricFactory implements MetricFactoryInterface
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
     * @var GuzzleClientFactory.
     */
    private $guzzleClientFactory;

    /**
     * @var string
     */
    private $name;

    public function __construct(ConfigInterface $config, CollectorRegistry $registry, GuzzleClientFactory $guzzleClientFactory)
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->guzzleClientFactory = $guzzleClientFactory;
        $this->name = $this->config->get('metric.default');
        $this->guardConfig();
    }

    public function makeCounter($name, $labelNames): CounterInterface
    {
        return new Counter(
            $this->registry,
            $this->getNamespace(),
            $name,
            'count ' . str_replace('_', ' ', $name),
            $labelNames
        );
    }

    public function makeGauge($name, $labelNames): GaugeInterface
    {
        return new Gauge(
            $this->registry,
            $this->getNamespace(),
            $name,
            'gauge ' . str_replace('_', ' ', $name),
            $labelNames
        );
    }

    public function makeHistogram($name, $labelNames): HistogramInterface
    {
        return new Histogram(
            $this->registry,
            $this->getNamespace(),
            $name,
            'measure ' . str_replace('_', ' ', $name),
            $labelNames
        );
    }

    public function handle(): void
    {
        
        switch ($this->config->get("metric.metric.{$this->name}.mode")) {
            case Constants::SCRAPE_MODE:
                $this->pullHandle();
                break;
            case Constants::PUSH_MODE:
                $this->pushHandle();
                break;
            default:
                throw new InvalidArgumentException('Unsupported Prometheus mode encountered');
                break;
        }
    }

    protected function pullHandle()
    {
        $host = $this->config->get("metric.metric.{$this->name}.scrape_host");
        $port = $this->config->get("metric.metric.{$this->name}.scrape_port");
        $path = $this->config->get("metric.metric.{$this->name}.scrape_path");
        $renderer = new RenderTextFormat();
        $server = new Server($host, (int) $port, false);
        $server->handle($path, function ($request, $response) use ($renderer) {
            $response->header('Content-Type', 'text/plain');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->end($renderer->render($this->registry->getMetricFamilySamples()));
        });
        $server->start();
    }

    protected function pushHandle()
    {
        while (true) {
            $inteval = $this->config->get("metric.metric.{$this->name}.push_inteval", 5);
            $host = $this->config->get("metric.metric.{$this->name}.push_host");
            $port = $this->config->get("metric.metric.{$this->name}.push_port");
            $this->doRequest("{$host}:{$port}", $this->getNamespace(), null, 'put');
            Coroutine::sleep($inteval);
        }
    }

    private function getNamespace(): string
    {
        return $this->config->get("metric.metric.{$this->name}.namespace");
    }

    private function guardConfig()
    {
        if ($this->config->get("metric.metric.{$this->name}.mode") == Constants::SCRAPE_MODE &&
            $this->config->get('metric.use_standalone_process') == false) {
            throw new RuntimeException(
                "Prometheus in pull mode must be used in conjunction with standalone process. \n Set metric.use_standalone_process to true to avoid this error."
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
