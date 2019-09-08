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
use Hyperf\Telemetry\Annotation\Counter;
use Hyperf\Telemetry\Contract\CounterInterface;

/**
 * @Aspect
 */
class TraceAnnotationAspect implements AroundInterface
{
    public $classes = [];

    public $annotations = [
        Counter::class,
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
        /** @var Counter $annotation */
        if ($annotation = $metadata->method[Counter::class] ?? null) {
            $name = $annotation->name;
        } else {
            $name = $source;
        }
        $counter = $this->factory->makeCounter($name, ['source']);
        $result = $proceedingJoinPoint->process();
        $counter->with($source)->add(1);
        return $result;
    }
}
