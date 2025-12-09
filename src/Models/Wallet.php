<?php

namespace Adichan\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Adichan\Wallet\Events\WalletCredited;
use Adichan\Wallet\Events\WalletDebited;

class Wallet extends Model
{
    protected $fillable = ['balance', 'owner_id', 'owner_type'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Add funds to the wallet.
     */
    public function credit(float $amount, string $description = ''): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive for credit.');
        }

        $this->increment('balance', $amount);
        $transaction = $this->transactions()->create([
            'type' => config('wallet.transaction_types.credit'),
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $this->balance,
        ]);

        // Dispatch event
        event(new WalletCredited($this, $transaction));

        return $transaction;
    }

    /**
     * Deduct funds from the wallet.
     */
    public function debit(float $amount, string $description = ''): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive for debit.');
        }

        if ($this->balance < $amount) {
            throw new \InvalidArgumentException('Insufficient balance.');
        }

        $this->decrement('balance', $amount);
        $transaction = $this->transactions()->create([
            'type' => config('wallet.transaction_types.debit'),
            'amount' => -$amount, // Store as negative for debits
            'description' => $description,
            'balance_after' => $this->balance,
        ]);

        // Dispatch event
        event(new WalletDebited($this, $transaction));

        return $transaction;
    }

    public function getBalanceAttribute($value): float
    {
        return (float) $value;
    }
}
