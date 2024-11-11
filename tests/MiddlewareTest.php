<?php

declare(strict_types=1);

namespace SlamTest\DbalDebugstackMiddleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Slam\DbalDebugstackMiddleware\DebugStack;
use Slam\DbalDebugstackMiddleware\Middleware;

final class MiddlewareTest extends TestCase
{
    public function testLogs(): void
    {
        $realDriver = $this->createMock(Driver::class);

        $stack      = new DebugStack();
        $middleware = new Middleware($stack);

        $driver = $middleware->wrap($realDriver);

        $queries1 = $stack->getQueries();
        self::assertSame([], $queries1);

        $params         = ['foo' => \uniqid('bar')];
        $realConnection = $this->createMock(Driver\Connection::class);
        $realDriver
            ->expects(self::once())
            ->method('connect')
            ->with(self::identicalTo($params))
            ->willReturn($realConnection)
        ;

        $connection = $driver->connect($params);

        $queries           = $stack->getQueries();
        $currentQueryIndex = 0;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame('CONNECT', $queries[$currentQueryIndex]->sql);
        self::assertSame($params, $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $realConnection
            ->expects(self::once())
            ->method('beginTransaction')
        ;
        $connection->beginTransaction();

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame('BEGINNING TRANSACTION', $queries[$currentQueryIndex]->sql);
        self::assertSame([], $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $realConnection
            ->expects(self::once())
            ->method('commit')
        ;
        $connection->commit();

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame('COMMITTING TRANSACTION', $queries[$currentQueryIndex]->sql);
        self::assertSame([], $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $realConnection
            ->expects(self::once())
            ->method('rollBack')
        ;
        $connection->rollBack();

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame('ROLLING BACK TRANSACTION', $queries[$currentQueryIndex]->sql);
        self::assertSame([], $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $sql = \uniqid('query_');
        $realConnection
            ->expects(self::once())
            ->method('query')
            ->with(self::identicalTo($sql))
            ->willReturn($realResult = $this->createMock(Driver\Result::class))
        ;
        self::assertSame($realResult, $connection->query($sql));

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame($sql, $queries[$currentQueryIndex]->sql);
        self::assertSame([], $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $sql = \uniqid('exec_');
        $realConnection
            ->expects(self::once())
            ->method('exec')
            ->with(self::identicalTo($sql))
            ->willReturn($realResult = \mt_rand(100, 199))
        ;
        self::assertSame($realResult, $connection->exec($sql));

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame($sql, $queries[$currentQueryIndex]->sql);
        self::assertSame([], $queries[$currentQueryIndex]->params);
        self::assertSame([], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);

        $sql = \uniqid('prepare_');
        $realConnection
            ->expects(self::once())
            ->method('prepare')
            ->with(self::identicalTo($sql))
            ->willReturn($realStatement = $this->createMock(Driver\Statement::class))
        ;
        $statement = $connection->prepare($sql);
        self::assertSame(
            $realStatement,
            (new ReflectionProperty(AbstractStatementMiddleware::class, 'wrappedStatement'))->getValue($statement)
        );

        $param2 = \uniqid('param2_');
        $var2   = \uniqid('var2_');
        $type2  = ParameterType::BOOLEAN;
        $statement->bindValue($param2, $var2, $type2);

        $realStatement
            ->expects(self::once())
            ->method('execute')
            ->willReturn($realResult = $this->createMock(Driver\Result::class))
        ;
        self::assertSame($realResult, $statement->execute());

        $queries = $stack->getQueries();
        ++$currentQueryIndex;
        self::assertCount(1 + $currentQueryIndex, $queries);
        self::assertSame($sql, $queries[$currentQueryIndex]->sql);
        self::assertSame([
            $param2 => $var2,
        ], $queries[$currentQueryIndex]->params);
        self::assertSame([
            $param2 => $type2,
        ], $queries[$currentQueryIndex]->types);
        self::assertGreaterThan(0, $queries[$currentQueryIndex]->executionMs);
    }
}
