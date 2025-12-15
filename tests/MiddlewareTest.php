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

        $queries = $stack->popQueries();
        self::assertSame([], $queries);

        $params         = ['foo' => \uniqid('bar')];
        $realConnection = $this->createMock(Driver\Connection::class);
        $realDriver
            ->expects($this->once())
            ->method('connect')
            ->with(self::identicalTo($params))
            ->willReturn($realConnection)
        ;

        $connection = $driver->connect($params);

        $getQueries = $stack->getQueries();
        $queries    = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame('CONNECT', $queries[0]->sql);
        self::assertSame($params, $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        self::assertSame($queries, $getQueries);

        $realConnection
            ->expects($this->once())
            ->method('beginTransaction')
        ;
        $connection->beginTransaction();

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame('BEGINNING TRANSACTION', $queries[0]->sql);
        self::assertSame([], $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        $realConnection
            ->expects($this->once())
            ->method('commit')
        ;
        $connection->commit();

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame('COMMITTING TRANSACTION', $queries[0]->sql);
        self::assertSame([], $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        $realConnection
            ->expects($this->once())
            ->method('rollBack')
        ;
        $connection->rollBack();

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame('ROLLING BACK TRANSACTION', $queries[0]->sql);
        self::assertSame([], $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        $sql = \uniqid('query_');
        $realConnection
            ->expects($this->once())
            ->method('query')
            ->with(self::identicalTo($sql))
            ->willReturn($realResult = $this->createMock(Driver\Result::class))
        ;
        self::assertSame($realResult, $connection->query($sql));

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame($sql, $queries[0]->sql);
        self::assertSame([], $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        $sql = \uniqid('exec_');
        $realConnection
            ->expects($this->once())
            ->method('exec')
            ->with(self::identicalTo($sql))
            ->willReturn($realResult = \mt_rand(100, 199))
        ;
        self::assertSame($realResult, $connection->exec($sql));

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame($sql, $queries[0]->sql);
        self::assertSame([], $queries[0]->params);
        self::assertSame([], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);

        $sql = \uniqid('prepare_');
        $realConnection
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('execute')
            ->willReturn($realResult = $this->createMock(Driver\Result::class))
        ;
        self::assertSame($realResult, $statement->execute());

        $queries = $stack->popQueries();
        self::assertCount(1, $queries);
        self::assertSame($sql, $queries[0]->sql);
        self::assertSame([
            $param2 => $var2,
        ], $queries[0]->params);
        self::assertSame([
            $param2 => $type2,
        ], $queries[0]->types);
        self::assertGreaterThan(0, $queries[0]->executionMs);
    }
}
