<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Adichan\Wallet\Models\Wallet;
use Adichan\Wallet\Services\WalletService;
use Adichan\Wallet\Tests\TestModels\User; // Assume a test User model

beforeEach(function () {
    $this->user = User::create(['name' => 'Test User']);
    $this->service = app(WalletService::class);
});

it('can create wallet for owner', function () {
    $wallet = Wallet::create(['owner_id' => $this->user->id, 'owner_type' => User::class]);
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
    $this->service->addFunds($this->user, 200.00);
    $this->service->deductFunds($this->user, 50.00, 'Withdrawal');
    $balance = $this->service->getBalance($this->user);
    expect($balance)->toBe(150.00);

    $transactions = $this->service->getTransactionHistory($this->user);
    expect($transactions)->toHaveCount(2);
    expect($transactions->last()->type)->toBe('debit');
    expect($transactions->last()->amount)->toBe(-50.00);
});

it('prevents debit with insufficient balance', function () {
    $this->service->addFunds($this->user, 10.00);
    $this->service->deductFunds($this->user, 20.00);
})->throws(\InvalidArgumentException::class, 'Insufficient balance.');

it('handles decimal precision', function () {
    $this->service->addFunds($this->user, 0.01);
    $balance = $this->service->getBalance($this->user);
    expect($balance)->toBe(0.01);
});

it('dispatches events on credit and debit', function () {
    Event::fake();

    $this->service->addFunds($this->user, 100.00);
    Event::assertDispatched(\Adichan\Wallet\Events\WalletCredited::class);

    $this->service->deductFunds($this->user, 50.00);
    Event::assertDispatched(\Adichan\Wallet\Events\WalletDebited::class);
});
