<?php

declare(strict_types=1);

namespace Slam\DbalDebugstackMiddleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

final class Statement extends AbstractStatementMiddleware
{
    /** @var array<int,mixed>|array<string,mixed> */
    private array $params = [];

    /** @var array<int,int>|array<string,int> */
    private array $types = [];

    public function __construct(
        StatementInterface $statement,
        private readonly DebugStack $debugStack,
        private readonly string $sql
    ) {
        parent::__construct($statement);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated use {@see bindValue()} instead
     */
    public function bindParam($param, & $variable, $type = ParameterType::STRING, $length = null)
    {
        $this->params[$param] = &$variable;
        $this->types[$param]  = $type;

        return parent::bindParam($param, $variable, $type, ...\array_slice(\func_get_args(), 3));
    }

    /** {@inheritDoc} */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        return parent::bindValue($param, $value, $type);
    }

    /** {@inheritDoc} */
    public function execute($params = null): ResultInterface
    {
        $start   = Query::start();
        $result  = parent::execute($params);
        $elapsed = Query::end($start);

        $this->debugStack->append(new Query(
            $this->sql,
            $params ?? $this->params,
            $this->types,
            $elapsed
        ));

        return $result;
    }
}
