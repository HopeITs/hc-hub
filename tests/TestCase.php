<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($app->environment('testing') && ($connection !== 'sqlite' || $database !== ':memory:')) {
            throw new RuntimeException(
                'Tests are blocked because the database is not SQLite :memory:. Clear cached configuration before running the suite.',
            );
        }

        return $app;
    }
}
