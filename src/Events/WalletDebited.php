<?php

namespace Adichan\Wallet\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Adichan\Wallet\Models\Wallet;
use Adichan\Wallet\Models\WalletTransaction;

class WalletDebited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Wallet $wallet,
        public WalletTransaction $transaction
    ) {}
}
