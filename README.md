# Slam\DbalDebugstackMiddleware

[![Latest Stable Version](https://img.shields.io/packagist/v/slam/dbal-debugstack-middleware.svg)](https://packagist.org/packages/slam/dbal-debugstack-middleware)
[![Downloads](https://img.shields.io/packagist/dt/slam/dbal-debugstack-middleware.svg)](https://packagist.org/packages/slam/dbal-debugstack-middleware)
[![Integrate](https://github.com/Slamdunk/dbal-debugstack-middleware/workflows/CI/badge.svg)](https://github.com/Slamdunk/dbal-debugstack-middleware/actions)

Doctrine\DBAL middleware for precise query debugging (DebugStack replacement).
Compared to the [default logging middleware](https://github.com/doctrine/dbal/pull/4967), this one:

1. Tracks the query's execution time
2. Doesn't handle exceptions
3. Doesn't track disconnections

## Installation

`composer require slam/dbal-debugstack-middleware`

## Usage

```php
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use Slam\DbalDebugstackMiddleware\DebugStack;
use Slam\DbalDebugstackMiddleware\Middleware;

$debugStack = new DebugStack();

$conn = DriverManager::getConnection(
    $connectionParams,
    (new Configuration)->setMiddlewares([
        new Middleware($debugStack)
    ])
);

$result = $conn->executeQuery(
    'SELECT * FROM users WHERE active = :active',
    ['active' => true],
    ['active' => ParameterType::BOOLEAN],
);

print_r($debugStack->popQueries());

/*
 * Output:
 *
    Array
    (
        [0] => Slam\DbalDebugstackMiddleware\Query Object
            (
                [sql] => SELECT * FROM users WHERE active = :active
                [params] => Array
                    (
                        [active] => true
                    )
                [types] => Array
                    (
                        [active] => 5
                    )
                [executionMs] => 72.05312
            )
    )
 */
```