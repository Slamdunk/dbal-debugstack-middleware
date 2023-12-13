<?php

declare(strict_types=1);

namespace Slam\DbalDebugstackMiddleware;

final class DebugStack
{
    /** @var list<Query> */
    private array $queries = [];

    public function append(Query $query): void
    {
        $this->queries[] = $query;
    }

    /** @return list<Query> */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
