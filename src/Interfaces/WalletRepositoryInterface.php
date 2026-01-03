<?php

namespace Adichan\Wallet\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    public function findOrCreateForOwner(Model $owner): Wallet;

    public function getBalance(Model $owner): float;

    public function getTransactions(Model $owner, int $limit = 10, int $offset = 0);

    public function getTransactionCount(Model $owner): int;

    public function getTotalCredits(Model $owner): float;

    public function getTotalDebits(Model $owner): float;

    public function getPaginatedTransactions(Model $owner, int $perPage = 15): LengthAwarePaginator;

    public function getTransactionsBetweenDates(Model $owner, \DateTime $startDate, \DateTime $endDate);

    public function getSummary(Model $owner): array;

    public function walletExists(Model $owner): bool;

    public function updateBalance(Model $owner, float $newBalance): bool;
}
