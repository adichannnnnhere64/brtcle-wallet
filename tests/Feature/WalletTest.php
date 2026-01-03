<?php

namespace Adichan\Wallet\Tests\Feature;

use Adichan\Wallet\Models\Wallet;
use Adichan\Wallet\Services\WalletService;
use Adichan\Wallet\Tests\TestModels\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure config is loaded
    config([
        'wallet.currency' => 'USD',
        'wallet.balance_precision' => 2,
        'wallet.transaction_types.credit' => 'credit',
        'wallet.transaction_types.debit' => 'debit',
        'wallet.minimum_balance' => 0,
        'wallet.maximum_balance' => 9999999.99,
        'wallet.cache.enabled' => false,
    ]);

    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->service = app(WalletService::class);
});

it('can create wallet for owner', function () {
    $wallet = Wallet::create([
        'owner_id' => $this->user->id,
        'owner_type' => User::class,
        'balance' => 0.00,
    ]);

    expect($wallet->balance)->toBe(0.0);
    expect($wallet->owner)->toBeInstanceOf(User::class);
});

it('can credit wallet', function () {
    $this->service->addFunds($this->user, 100.50, 'Initial deposit');

    $balance = $this->service->getBalance($this->user);
    expect($balance)->toBe(100.50);

    $transactions = $this->service->getTransactionHistory($this->user);
    expect($transactions)->toHaveCount(1);
    expect($transactions->first()->type)->toBe('credit');
    expect($transactions->first()->amount)->toBe(100.50);
});

it('can debit wallet', function () {
    $this->service->addFunds($this->user, 200.00, 'Initial deposit');
    $this->service->deductFunds($this->user, 50.00, 'Withdrawal');

    $balance = $this->service->getBalance($this->user);
    expect($balance)->toBe(150.00);

    $transactions = $this->service->getTransactionHistory($this->user);
    expect($transactions)->toHaveCount(2);
    expect($transactions->last()->type)->toBe('debit');
    expect($transactions->last()->amount)->toBe(-50.00);
});

it('prevents debit with insufficient balance', function () {
    $this->service->addFunds($this->user, 10.00, 'Initial deposit');

    expect(fn () => $this->service->deductFunds($this->user, 20.00, 'Overdraft'))
        ->toThrow(\InvalidArgumentException::class, 'Insufficient balance');
});

it('handles decimal precision', function () {
    $this->service->addFunds($this->user, 0.01, 'Small deposit');

    $balance = $this->service->getBalance($this->user);
    expect($balance)->toBe(0.01);
});

it('dispatches events on credit and debit', function () {
    Event::fake();

    $this->service->addFunds($this->user, 100.00, 'Test credit');
    Event::assertDispatched(\Adichan\Wallet\Events\WalletCredited::class);

    $this->service->addFunds($this->user, 100.00, 'Add more funds');
    $this->service->deductFunds($this->user, 50.00, 'Test debit');
    Event::assertDispatched(\Adichan\Wallet\Events\WalletDebited::class);
});

it('validates minimum amount', function () {
    expect(fn () => $this->service->addFunds($this->user, 0, 'Zero amount'))
        ->toThrow(\InvalidArgumentException::class, 'Amount must be greater than zero');
});

it('validates negative amount', function () {
    expect(fn () => $this->service->addFunds($this->user, -10.00, 'Negative amount'))
        ->toThrow(\InvalidArgumentException::class, 'Amount must be greater than zero');
});

it('can transfer funds between users', function () {
    $user2 = User::create([
        'name' => 'User 2',
        'email' => 'user2@example.com',
    ]);

    $this->service->addFunds($this->user, 100.00, 'Initial deposit');

    $success = $this->service->transferFunds(
        $this->user,
        $user2,
        50.00,
        'Test transfer'
    );

    expect($success)->toBeTrue();
    expect($this->service->getBalance($this->user))->toBe(50.00);
    expect($this->service->getBalance($user2))->toBe(50.00);
});

it('gets wallet summary', function () {
    $this->service->addFunds($this->user, 100.00, 'Deposit 1');
    $this->service->addFunds($this->user, 50.00, 'Deposit 2');
    $this->service->deductFunds($this->user, 30.00, 'Withdrawal');

    $summary = $this->service->getSummary($this->user);

    expect($summary)->toBeArray();
    expect($summary['balance'])->toBe(120.00);
    expect($summary['total_credits'])->toBe(150.00);
    expect($summary['total_debits'])->toBe(30.00);
    expect($summary['total_transactions'])->toBe(3);
});
