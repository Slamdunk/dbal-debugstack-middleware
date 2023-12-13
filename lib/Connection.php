<?php

declare(strict_types=1);

namespace Slam\DbalDebugstackMiddleware;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;

final class Connection extends AbstractConnectionMiddleware
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly DebugStack $debugStack,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new Statement(
            parent::prepare($sql),
            $this->debugStack,
            $sql,
        );
    }

    public function query(string $sql): Result
    {
        $start   = Query::start();
        $result  = parent::query($sql);
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query($sql, [], [], $elapsed));

        return $result;
    }

    public function exec(string $sql): int
    {
        $start   = Query::start();
        $result  = parent::exec($sql);
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query($sql, [], [], $elapsed));

        return $result;
    }

    /** {@inheritDoc} */
    public function beginTransaction()
    {
        $start   = Query::start();
        $result  = parent::beginTransaction();
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query('BEGINNING TRANSACTION', [], [], $elapsed));

        return $result;
    }

    /** {@inheritDoc} */
    public function commit()
    {
        $start   = Query::start();
        $result  = parent::commit();
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query('COMMITTING TRANSACTION', [], [], $elapsed));

        return $result;
    }

    /** {@inheritDoc} */
    public function rollBack()
    {
        $start   = Query::start();
        $result  = parent::rollBack();
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query('ROLLING BACK TRANSACTION', [], [], $elapsed));

        return $result;
    }
}
