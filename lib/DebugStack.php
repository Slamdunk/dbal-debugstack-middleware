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

    /**
     * @return list<Query>
     *
     * @deprecated Consider using {@see self::popQueries()} instead
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /** @return list<Query> */
    public function popQueries(): array
    {
        $queries = $this->queries;

        $this->queries = [];

        return $queries;
    }
}
