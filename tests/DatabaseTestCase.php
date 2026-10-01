<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();

        // Fail before RefreshDatabase can migrate any non-test database.
        if (! $app->environment('testing') || $connection->getDriverName() !== 'mysql'
            || ! str_ends_with($connection->getDatabaseName(), '_test')) {
            throw new LogicException('Use an isolated MySQL/MariaDB database ending in _test.');
        }

        return $app;
    }
}
