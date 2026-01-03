<?php

namespace Adichan\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected WalletRepositoryInterface $repository
    ) {}

    public function addFunds(Model $owner, float $amount, string $description = '', array $meta = []): void
    {
        $this->validateAmount($amount);
        
        DB::transaction(function () use ($owner, $amount, $description, $meta) {
            $wallet = $this->repository->findOrCreateForOwner($owner);
            
            // Check maximum balance
            $maxBalance = config('wallet.maximum_balance', 9999999.99);
            if (($wallet->balance + $amount) > $maxBalance) {
                throw new \InvalidArgumentException(
                    "Adding {$amount} would exceed maximum balance of {$maxBalance}"
                );
            }
            
            $transaction = $wallet->credit($amount, $description);
            
            // Add metadata if provided
            if (!empty($meta)) {
                $transaction->update(['meta' => $meta]);
            }
        });
    }

    public function deductFunds(Model $owner, float $amount, string $description = '', array $meta = []): void
    {
        $this->validateAmount($amount);
        
        DB::transaction(function () use ($owner, $amount, $description, $meta) {
            $wallet = $this->repository->findOrCreateForOwner($owner);
            
            // Check minimum balance
            $minBalance = config('wallet.minimum_balance', 0);
            if (($wallet->balance - $amount) < $minBalance) {
                throw new \InvalidArgumentException(
                    "Insufficient balance. Minimum allowed balance is {$minBalance}"
                );
            }
            
            $transaction = $wallet->debit($amount, $description);
            
            // Add metadata if provided
            if (!empty($meta)) {
                $transaction->update(['meta' => $meta]);
            }
        });
    }

    public function getBalance(Model $owner): float
    {
        return $this->repository->getBalance($owner);
    }

    public function getTransactionHistory(Model $owner, int $limit = 10, int $offset = 0)
    {
        return $this->repository->getTransactions($owner, $limit, $offset);
    }

    public function getTransactionCount(Model $owner): int
    {
        return $this->repository->getTransactionCount($owner);
    }

    public function getTotalCredits(Model $owner): float
    {
        return $this->repository->getTotalCredits($owner);
    }

    public function getTotalDebits(Model $owner): float
    {
        return $this->repository->getTotalDebits($owner);
    }

    public function getPaginatedTransactions(Model $owner, int $perPage = 15)
    {
        return $this->repository->getPaginatedTransactions($owner, $perPage);
    }

    public function getSummary(Model $owner): array
    {
        return $this->repository->getSummary($owner);
    }

    public function transferFunds(Model $from, Model $to, float $amount, string $description = '', array $meta = []): bool
    {
        DB::beginTransaction();
        
        try {
            // Deduct from sender
            $this->deductFunds($from, $amount, "Transfer to {$to->getMorphClass()} #{$to->getKey()}: {$description}", $meta);
            
            // Add to recipient
            $this->addFunds($to, $amount, "Transfer from {$from->getMorphClass()} #{$from->getKey()}: {$description}", $meta);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }
        
        if ($amount > config('wallet.maximum_balance', 9999999.99)) {
            throw new \InvalidArgumentException('Amount exceeds maximum allowed');
        }
    }
}
