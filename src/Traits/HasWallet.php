<?php

namespace Adichan\Wallet\Traits;

use Adichan\Wallet\Services\WalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait HasWallet
{
    /**
     * Get wallet service instance.
     */
    protected function getWalletService(): WalletService
    {
        return app(WalletService::class);
    }

    /**
     * Add funds to wallet.
     */
    public function addFunds(float $amount, string $description = '', array $meta = []): void
    {
        $this->getWalletService()->addFunds($this, $amount, $description, $meta);
        $this->clearWalletCache();
    }

    /**
     * Deduct funds from wallet.
     */
    public function deductFunds(float $amount, string $description = '', array $meta = []): void
    {
        $this->getWalletService()->deductFunds($this, $amount, $description, $meta);
        $this->clearWalletCache();
    }

    /**
     * Get current balance.
     */
    public function getBalance(): float
    {
        if (! config('wallet.cache.enabled')) {
            return $this->getWalletService()->getBalance($this);
        }

        $cacheKey = $this->getWalletCacheKey('balance');

        return Cache::remember(
            $cacheKey,
            config('wallet.cache.ttl'),
            fn () => $this->getWalletService()->getBalance($this)
        );
    }

    /**
     * Get wallet transaction history.
     */
    public function getWalletHistory(int $limit = 10, int $offset = 0)
    {
        if (! config('wallet.cache.enabled')) {
            return $this->getWalletService()->getTransactionHistory($this, $limit, $offset);
        }

        $cacheKey = $this->getWalletCacheKey("history_{$limit}_{$offset}");

        return Cache::remember(
            $cacheKey,
            config('wallet.cache.ttl'),
            fn () => $this->getWalletService()->getTransactionHistory($this, $limit, $offset)
        );
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->getBalance() >= $amount;
    }

    /**
     * Transfer funds to another wallet.
     */

    /**
     * Transfer funds to another wallet.
     */
    /**
     * Transfer funds to another model that has wallet capability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $recipient  The recipient model (must use HasWallet trait)
     * @param  float  $amount  Amount to transfer
     * @param  string  $description  Transfer description
     * @param  array  $meta  Additional metadata
     * @return bool True if transfer successful, false otherwise
     */
    public function transferFunds(Model $recipient, float $amount, string $description = '', array $meta = []): bool
    {
        // Early exit: check sender balance
        if (! $this->hasSufficientBalance($amount)) {
            \Log::warning('Transfer failed: Insufficient balance', [
                'sender' => $this->getKey(),
                'recipient' => $recipient->getKey(),
                'amount' => $amount,
            ]);

            return false;
        }


        try {
            return $this->getWalletService()->transferFunds($this, $recipient, $amount, $description, $meta);
        } catch (\Exception $e) {
            \Log::error('Wallet transfer failed: '.$e->getMessage(), [
                'sender' => $this->getKey(),
                'recipient' => $recipient->getKey(),
                'amount' => $amount,
                'exception' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get wallet summary.
     */
    public function getWalletSummary(): array
    {
        return [
            'balance' => $this->getBalance(),
            'currency' => config('wallet.currency'),
            'owner_type' => $this->getMorphClass(),
            'owner_id' => $this->getKey(),
            'total_credits' => $this->getTotalCredits(),
            'total_debits' => $this->getTotalDebits(),
            'transaction_count' => $this->getTransactionCount(),
        ];
    }

    /**
     * Get total credits.
     */
    public function getTotalCredits(): float
    {
        if (! config('wallet.cache.enabled')) {
            return $this->getWalletService()->getTotalCredits($this);
        }

        $cacheKey = $this->getWalletCacheKey('total_credits');

        return Cache::remember(
            $cacheKey,
            config('wallet.cache.ttl'),
            fn () => $this->getWalletService()->getTotalCredits($this)
        );
    }

    /**
     * Get total debits.
     */
    public function getTotalDebits(): float
    {
        if (! config('wallet.cache.enabled')) {
            return $this->getWalletService()->getTotalDebits($this);
        }

        $cacheKey = $this->getWalletCacheKey('total_debits');

        return Cache::remember(
            $cacheKey,
            config('wallet.cache.ttl'),
            fn () => $this->getWalletService()->getTotalDebits($this)
        );
    }

    /**
     * Get transaction count.
     */
    public function getTransactionCount(): int
    {
        if (! config('wallet.cache.enabled')) {
            return $this->getWalletService()->getTransactionCount($this);
        }

        $cacheKey = $this->getWalletCacheKey('transaction_count');

        return Cache::remember(
            $cacheKey,
            config('wallet.cache.ttl'),
            fn () => $this->getWalletService()->getTransactionCount($this)
        );
    }

    /**
     * Clear wallet cache.
     */
    public function clearWalletCache(): void
    {
        if (config('wallet.cache.enabled')) {
            $prefix = config('wallet.cache.prefix');
            $ownerKey = "{$this->getMorphClass()}_{$this->getKey()}";
            Cache::deleteMultiple([
                "{$prefix}{$ownerKey}_balance",
                "{$prefix}{$ownerKey}_total_credits",
                "{$prefix}{$ownerKey}_total_debits",
                "{$prefix}{$ownerKey}_transaction_count",
            ]);

            // Clear paginated history cache (more complex - might need pattern matching)
            // For simplicity, we'll rely on TTL for paginated data
        }
    }

    /**
     * Get wallet cache key.
     */
    protected function getWalletCacheKey(string $type): string
    {
        $prefix = config('wallet.cache.prefix');
        $ownerKey = "{$this->getMorphClass()}_{$this->getKey()}";

        return "{$prefix}{$ownerKey}_{$type}";
    }

    /**
     * Scope for models with minimum balance.
     */
    public function scopeHasMinimumBalance($query, float $amount)
    {
        return $query->whereHas('wallet', function ($q) use ($amount) {
            $q->where('balance', '>=', $amount);
        });
    }

    /**
     * Scope for models with insufficient balance.
     */
    public function scopeHasInsufficientBalance($query, float $amount)
    {
        return $query->whereHas('wallet', function ($q) use ($amount) {
            $q->where('balance', '<', $amount);
        });
    }

    /**
     * Check if balance is below minimum allowed.
     */
    public function isBalanceBelowMinimum(): bool
    {
        return $this->getBalance() < config('wallet.minimum_balance', 0);
    }

    /**
     * Check if balance exceeds maximum allowed.
     */
    public function isBalanceExceedsMaximum(): bool
    {
        return $this->getBalance() > config('wallet.maximum_balance', 9999999.99);
    }
}
