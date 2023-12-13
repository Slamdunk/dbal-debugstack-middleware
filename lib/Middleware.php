<?php

declare(strict_types=1);

namespace Slam\DbalDebugstackMiddleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

final readonly class Middleware implements MiddlewareInterface
{
    public function __construct(
        private DebugStack $debugStack,
    ) {}

    public function wrap(DriverInterface $driver): Driver
    {
        return new Driver($driver, $this->debugStack);
    }
}
