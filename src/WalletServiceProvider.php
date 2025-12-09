<?php

namespace Adichan\Wallet;

use Adichan\Wallet\Interfaces\WalletRepositoryInterface;
use Adichan\Wallet\Repositories\WalletRepository;
use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/wallet.php', 'wallet');

        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/wallet.php' => config_path('wallet.php'),
            ], 'config');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'migrations');
    }
}
