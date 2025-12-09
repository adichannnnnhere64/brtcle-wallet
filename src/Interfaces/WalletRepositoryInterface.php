<?php

namespace Adichan\Wallet\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findOrCreateForOwner(Model $owner): Wallet;

    public function getBalance(Model $owner): float;

    public function getTransactions(Model $owner, int $limit = 10);
}
