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

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

/**
 * Class Process.
 * @Process
 */
class TelemetryProcess extends AbstractProcess
{
    public $name = 'telemetry';
    public $nums = 1;
    /**
     * The logical of process will place in here.
     */
    public function handle(): void
    {
        $registry = new CollectorRegistry(new InMemory());
        $renderer = new RenderTextFormat();
        $http = new Swoole\Http\Server("127.0.0.1", 9502);
        $http->on('request', function ($request, $response) {
            $response->end($renderer->render($registry->getMetricFamilySamples()));
        });
        $http->start();
    }
}
