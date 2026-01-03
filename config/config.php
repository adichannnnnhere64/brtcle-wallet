<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the wallet package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for wallet balances and transactions.
    |
    */
    'currency' => env('WALLET_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Balance Precision
    |--------------------------------------------------------------------------
    |
    | Number of decimal places to store for wallet balances.
    | Recommended: 2 for most currencies, 0 for some like JPY.
    |
    */
    'balance_precision' => env('WALLET_PRECISION', 2),

    /*
    |--------------------------------------------------------------------------
    | Transaction Types
    |--------------------------------------------------------------------------
    |
    | Define the transaction types used in the wallet system.
    |
    */
    'transaction_types' => [
        'credit' => 'credit',
        'debit' => 'debit',
        'transfer' => 'transfer',
        'refund' => 'refund',
        'commission' => 'commission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Balance
    |--------------------------------------------------------------------------
    |
    | The minimum allowed balance for wallets (can be negative for overdraft).
    |
    */
    'minimum_balance' => env('WALLET_MINIMUM_BALANCE', 0),

    /*
    |--------------------------------------------------------------------------
    | Maximum Balance
    |--------------------------------------------------------------------------
    |
    | The maximum allowed balance for wallets.
    |
    */
    'maximum_balance' => env('WALLET_MAXIMUM_BALANCE', 9999999.99),

    /*
    |--------------------------------------------------------------------------
    | Wallet Owner Morph Map
    |--------------------------------------------------------------------------
    |
    | Map morph types to specific classes for better performance.
    | Example: ['user' => \App\Models\User::class]
    |
    */
    'morph_map' => [],

    /*
    |--------------------------------------------------------------------------
    | Wallet Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable wallet events.
    |
    */
    'events' => [
        'enabled' => env('WALLET_EVENTS_ENABLED', true),
        'broadcast' => env('WALLET_EVENTS_BROADCAST', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Model
    |--------------------------------------------------------------------------
    |
    | Custom wallet model class.
    |
    */
    'models' => [
        'wallet' => \Adichan\Wallet\Models\Wallet::class,
        'wallet_transaction' => \Adichan\Wallet\Models\WalletTransaction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Service
    |--------------------------------------------------------------------------
    |
    | Custom service classes.
    |
    */
    'services' => [
        'wallet_service' => \Adichan\Wallet\Services\WalletService::class,
        'repository' => \Adichan\Wallet\Repositories\WalletRepository::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Serialization
    |--------------------------------------------------------------------------
    |
    | Configure how transactions are serialized for storage.
    |
    */
    'serialization' => [
        'encrypt' => env('WALLET_ENCRYPT_TRANSACTIONS', false),
        'compress' => env('WALLET_COMPRESS_TRANSACTIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for wallet balances and transactions.
    |
    */
    'cache' => [
        'enabled' => env('WALLET_CACHE_ENABLED', true),
        'ttl' => env('WALLET_CACHE_TTL', 3600), // seconds
        'prefix' => env('WALLET_CACHE_PREFIX', 'wallet_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for wallet operations.
    |
    */
    'queue' => [
        'transactions' => env('WALLET_QUEUE_TRANSACTIONS', false),
        'connection' => env('WALLET_QUEUE_CONNECTION', 'default'),
        'queue' => env('WALLET_QUEUE_NAME', 'wallet'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | API settings if you plan to expose wallet functionality via API.
    |
    */
    'api' => [
        'enabled' => env('WALLET_API_ENABLED', false),
        'prefix' => env('WALLET_API_PREFIX', 'api/wallet'),
        'middleware' => ['api'],
        'throttle' => env('WALLET_API_THROTTLE', '60,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for wallet operations.
    |
    */
    'validation' => [
        'amount' => 'required|numeric|min:0.01',
        'description' => 'nullable|string|max:255',
    ],
];
