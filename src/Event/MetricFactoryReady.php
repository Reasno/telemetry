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

namespace Hyperf\Metric\Event;

use Hyperf\Metric\Contract\MetricFactoryInterface;

class MetricFactoryReady
{
    /**
     * A ready to use factory
     * @var MetricFactoryInterface
     */
    public $factory;

    public function __construct(MetricFactoryInterface $factory)
    {
        $this->factory = $factory;
    }
}
