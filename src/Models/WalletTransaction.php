<?php

namespace Adichan\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = ['type', 'amount', 'description', 'balance_after', 'meta'];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Scope for credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('type', config('wallet.transaction_types.credit'));
    }

    /**
     * Scope for debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('type', config('wallet.transaction_types.debit'));
    }

    /**
     * Scope for transactions between dates.
     */
    public function scopeBetweenDates($query, \DateTime $startDate, \DateTime $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        $currency = config('wallet.currency', 'USD');
        $precision = config('wallet.balance_precision', 2);
        
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . number_format($this->amount, $precision) . ' ' . $currency;
    }

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->type === config('wallet.transaction_types.credit');
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->type === config('wallet.transaction_types.debit');
    }
}
