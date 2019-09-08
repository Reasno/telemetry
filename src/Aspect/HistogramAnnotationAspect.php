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

namespace Hyperf\Telemetry\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Telemetry\Annotation\Histogram;
use Hyperf\Telemetry\Contract\HistogramInterface;
use Hyperf\Telemetry\Timer;

/**
 * @Aspect
 */
class TraceAnnotationAspect implements AroundInterface
{
    public $classes = [];

    public $annotations = [
        Histogram::class,
    ];

    /**
     * @var TelemetryFactoryInterface
     */
    private $factory;

    public function __construct(TelemetryFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $source = $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName;
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();
        /** @var Histogram $annotation */
        if ($annotation = $metadata->method[Histogram::class] ?? null) {
            $name = $annotation->name;
        } else {
            $name = $source;
        }
        /** @var Timer $timer */
        $timer = new Timer($this->factory->makeHistogram($name, ['source'])->with($source));
        $result = $proceedingJoinPoint->process();
        $timer->observeDuration();
        return $result;
    }
}
