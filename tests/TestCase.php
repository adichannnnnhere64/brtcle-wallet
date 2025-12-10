<?php

namespace Adichan\Wallet\Tests;

/* use Illuminate\Foundation\Testing\TestCase as BaseTestCase; */

use Adichan\Wallet\WalletServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadTestMigrations();
    }

    protected function loadTestMigrations()
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            WalletServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

}
