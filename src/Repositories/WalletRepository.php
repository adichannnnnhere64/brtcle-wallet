<?php

namespace Adichan\Wallet\Repositories;

use Adichan\Wallet\Interfaces\WalletRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Adichan\Wallet\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

    public function getTransactions(Model $owner, int $limit = 10, int $offset = 0)
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->transactions()
            ->latest()
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    public function getTransactionCount(Model $owner): int
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->transactions()->count();
    }

    public function getTotalCredits(Model $owner): float
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return (float) $wallet->transactions()
            ->where('type', config('wallet.transaction_types.credit'))
            ->sum('amount');
    }

    public function getTotalDebits(Model $owner): float
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return abs((float) $wallet->transactions()
            ->where('type', config('wallet.transaction_types.debit'))
            ->sum('amount'));
    }

    public function getPaginatedTransactions(Model $owner, int $perPage = 15): LengthAwarePaginator
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->transactions()
            ->latest()
            ->paginate($perPage);
    }

    public function getTransactionsBetweenDates(Model $owner, \DateTime $startDate, \DateTime $endDate)
    {
        $wallet = $this->findOrCreateForOwner($owner);
        return $wallet->transactions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();
    }

    public function getSummary(Model $owner): array
    {
        $wallet = $this->findOrCreateForOwner($owner);
        
        $summary = DB::table('wallet_transactions')
            ->selectRaw("
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN type = ? THEN ABS(amount) ELSE 0 END) as total_debits
            ", [
                config('wallet.transaction_types.credit'),
                config('wallet.transaction_types.debit')
            ])
            ->where('wallet_id', $wallet->id)
            ->first();

        return [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'currency' => config('wallet.currency'),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'total_credits' => (float) ($summary->total_credits ?? 0),
            'total_debits' => (float) ($summary->total_debits ?? 0),
            'net_flow' => ((float) ($summary->total_credits ?? 0)) - ((float) ($summary->total_debits ?? 0)),
            'created_at' => $wallet->created_at,
            'updated_at' => $wallet->updated_at,
        ];
    }

    public function walletExists(Model $owner): bool
    {
        return Wallet::where([
            'owner_id' => $owner->getKey(),
            'owner_type' => $owner->getMorphClass(),
        ])->exists();
    }

    public function updateBalance(Model $owner, float $newBalance): bool
    {
        $wallet = $this->findOrCreateForOwner($owner);
        
        // Validate new balance
        $minBalance = config('wallet.minimum_balance', 0);
        $maxBalance = config('wallet.maximum_balance', 9999999.99);
        
        if ($newBalance < $minBalance || $newBalance > $maxBalance) {
            throw new \InvalidArgumentException(
                "Balance must be between {$minBalance} and {$maxBalance}"
            );
        }
        
        return $wallet->update(['balance' => $newBalance]);
    }
}
