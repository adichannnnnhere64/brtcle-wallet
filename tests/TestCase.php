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
        
        // Load configuration
        config([
            'wallet.currency' => 'USD',
            'wallet.balance_precision' => 2,
            'wallet.transaction_types.credit' => 'credit',
            'wallet.transaction_types.debit' => 'debit',
            'wallet.minimum_balance' => 0,
            'wallet.maximum_balance' => 9999999.99,
            'wallet.cache.enabled' => false,
            'wallet.events.enabled' => false,
            'wallet.api.enabled' => false,
        ]);
    }

    protected function loadTestMigrations()
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        if (! $schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }
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
        $this->loadTestMigrations();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        $app['config']->set('cache.default', 'array');
    }

}
