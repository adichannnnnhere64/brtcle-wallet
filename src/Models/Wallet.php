<?php

namespace Adichan\Wallet\Models;

use Adichan\Wallet\Events\WalletCredited;
use Adichan\Wallet\Events\WalletDebited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = ['balance', 'owner_id', 'owner_type'];

    protected $casts = [
        'balance' => 'decimal:2',
        'meta' => 'array',
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
    /**
     * Add funds to the wallet.
     */
    public function credit(float $amount, string $description = ''): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive for credit.');
        }

        // Round to configured precision
        $precision = config('wallet.balance_precision', 2);
        $roundedAmount = round($amount, $precision);

        // If amount rounds to zero, just create a transaction with zero amount
        if ($roundedAmount <= 0) {
            return $this->transactions()->create([
                'type' => config('wallet.transaction_types.credit'),
                'amount' => 0,
                'description' => $description.' (rounded to zero)',
                'balance_after' => $this->balance,
            ]);
        }

        // Calculate new balance with proper precision
        $newBalance = round($this->balance + $roundedAmount, $precision);

        $this->update(['balance' => $newBalance]);
        $this->refresh();

        $transaction = $this->transactions()->create([
            'type' => config('wallet.transaction_types.credit'),
            'amount' => $roundedAmount,
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
