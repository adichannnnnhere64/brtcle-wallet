# Adichan Wallet Package

A comprehensive wallet system for Laravel applications with support for multiple transaction types, caching, events, and API endpoints.

## Features

- ✅ Multiple wallet support per model
- ✅ Transaction history with pagination
- ✅ Built-in caching for performance
- ✅ Event system for wallet operations
- ✅ API endpoints (optional)
- ✅ Transfer between wallets
- ✅ Validation rules
- ✅ Decimal precision control
- ✅ Minimum/maximum balance limits
- ✅ Morph map support
- ✅ Queue support for transactions

## Installation

```bash
composer require adichan/wallet


$user = User::find(1);

// Add funds
$user->addFunds(100.00, 'Deposit');

// Deduct funds
$user->deductFunds(50.00, 'Purchase');

// Get balance
$balance = $user->getBalance();

// Check sufficient balance
if ($user->hasSufficientBalance(75.00)) {
    // Proceed with transaction
}

// Get transaction history
$transactions = $user->getWalletHistory(10);

// Transfer funds
$user->transferFunds($recipient, 25.00, 'Payment');

// Get wallet summary
$summary = $user->getWalletSummary();
