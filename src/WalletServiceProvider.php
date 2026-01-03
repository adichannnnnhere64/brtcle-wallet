<?php

namespace Adichan\Wallet;

use Adichan\Wallet\Interfaces\WalletRepositoryInterface;
use Adichan\Wallet\Repositories\WalletRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/wallet.php', 'wallet');

        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        
        // Register facade
        $this->app->singleton('wallet', function ($app) {
            return $app->make(\Adichan\Wallet\Services\WalletService::class);
        });
        
        // Register macros
        $this->registerMacros();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/wallet.php' => config_path('wallet.php'),
            ], 'wallet-config');

            $this->publishes([
                __DIR__ . '/database/migrations/' => database_path('migrations')
            ], 'wallet-migrations');
            
            $this->publishes([
                __DIR__ . '/Traits/HasWallet.php' => app_path('Traits/HasWallet.php'),
            ], 'wallet-trait');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Register API routes if enabled
        if (config('wallet.api.enabled')) {
            $this->registerRoutes();
        }
        
        // Register morph map if configured
        $this->registerMorphMap();
    }
    
    protected function registerRoutes(): void
    {
        Route::prefix(config('wallet.api.prefix'))
            ->middleware(config('wallet.api.middleware'))
            ->group(function () {
                // Basic wallet routes
                Route::get('/balance', 'Adichan\Wallet\Http\Controllers\WalletController@balance');
                Route::get('/transactions', 'Adichan\Wallet\Http\Controllers\WalletController@transactions');
                Route::post('/add-funds', 'Adichan\Wallet\Http\Controllers\WalletController@addFunds');
                Route::post('/deduct-funds', 'Adichan\Wallet\Http\Controllers\WalletController@deductFunds');
                Route::post('/transfer', 'Adichan\Wallet\Http\Controllers\WalletController@transfer');
            });
    }
    
    protected function registerMorphMap(): void
    {
        $morphMap = config('wallet.morph_map', []);
        if (!empty($morphMap)) {
            \Illuminate\Database\Eloquent\Relations\Relation::morphMap($morphMap);
        }
    }
    
    protected function registerMacros(): void
    {
        // Collection macro to sum wallet balances
        \Illuminate\Support\Collection::macro('sumWalletBalances', function () {
            return $this->reduce(function ($carry, $model) {
                if (method_exists($model, 'getBalance')) {
                    return $carry + $model->getBalance();
                }
                return $carry;
            }, 0);
        });
        
        // Collection macro to filter by minimum balance
        \Illuminate\Support\Collection::macro('hasMinimumBalance', function (float $amount) {
            return $this->filter(function ($model) use ($amount) {
                return method_exists($model, 'hasSufficientBalance') && 
                       $model->hasSufficientBalance($amount);
            });
        });
    }
}
