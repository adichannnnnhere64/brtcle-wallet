<?php

namespace Adichan\Wallet\Repositories;

use Adichan\Wallet\Interfaces\WalletRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Models\Wallet;

class WalletRepository implements WalletRepositoryInterface
{
    public function findOrCreateForOwner(Model $owner): Wallet
    {
        return Wallet::firstOrCreate([
            'owner_id' => $owner->getKey(),
            'owner_type' => $owner->getMorphClass(),
        ], ['balance' => 0.00]);
    }

    public function getBalance(Model $owner): float
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->balance;
    }

    public function getTransactions(Model $owner, int $limit = 10)
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->transactions()->latest()->limit($limit)->get();
    }
}
