<?php

namespace Adichan\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = ['type', 'amount', 'description', 'balance_after'];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float'
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
