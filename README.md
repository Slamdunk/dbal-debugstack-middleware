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

var_dump($debugStack->getQueries());

/*
 * Output:
 *
    array(1) {
      [0] =>
      class Slam\DbalDebugstackMiddleware\Query#437 (4) {
        public readonly string $sql =>
        string(7) "SELECT * FROM users WHERE active = :active"
        public readonly array $params =>
        array(1) {
          'active' =>
          bool(true)
        }
        public readonly array $types =>
        array(1) {
          'active' =>
          int(5)
        }
        public readonly float $executionMs =>
        double(72.05312)
      }
    }
 */
```