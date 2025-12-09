<?php

namespace Adichan\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Interfaces\WalletRepositoryInterface;

class WalletService
{
    public function __construct(
        protected WalletRepositoryInterface $repository
    ) {}

    public function addFunds(Model $owner, float $amount, string $description = ''): void
    {
        $wallet = $this->repository->findOrCreateForOwner($owner);
        $wallet->credit($amount, $description);
    }

    public function deductFunds(Model $owner, float $amount, string $description = ''): void
    {
        $wallet = $this->repository->findOrCreateForOwner($owner);
        $wallet->debit($amount, $description);
    }

    public function getBalance(Model $owner): float
    {
        return $this->repository->getBalance($owner);
    }

    public function getTransactionHistory(Model $owner, int $limit = 10)
    {
        return $this->repository->getTransactions($owner, $limit);
    }
}
