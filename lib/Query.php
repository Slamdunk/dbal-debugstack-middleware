<?php

declare(strict_types=1);

namespace Slam\DbalDebugstackMiddleware;

use Doctrine\DBAL\ParameterType;

final readonly class Query
{
    /**
     * @param array<int,mixed>|array<string,mixed>                 $params
     * @param array<int,ParameterType>|array<string,ParameterType> $types
     */
    public function __construct(
        public string $sql,
        public array $params,
        public array $types,
        public float $executionMs,
    ) {}

    /** @internal */
    public static function start(): float
    {
        return \microtime(true);
    }

    /** @internal */
    public static function end(float $start): float
    {
        return \microtime(true) - $start;
    }
}
