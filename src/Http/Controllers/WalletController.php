<?php

namespace Adichan\Wallet\Http\Controllers;

use Adichan\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function balance(Request $request)
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        return response()->json([
            'balance' => $balance,
            'currency' => config('wallet.currency'),
            'formatted' => number_format($balance, config('wallet.balance_precision', 2)).' '.config('wallet.currency'),
        ]);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $transactions = $this->walletService->getPaginatedTransactions($user, $perPage);

        return response()->json($transactions);
    }

    public function addFunds(Request $request)
    {
        $request->validate([
            'amount' => config('wallet.validation.amount'),
            'description' => config('wallet.validation.description'),
        ]);

        try {
            $user = $request->user();
            $this->walletService->addFunds(
                $user,
                $request->amount,
                $request->description,
                $request->get('meta', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'new_balance' => $this->walletService->getBalance($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function deductFunds(Request $request)
    {
        $request->validate([
            'amount' => config('wallet.validation.amount'),
            'description' => config('wallet.validation.description'),
        ]);

        try {
            $user = $request->user();
            $this->walletService->deductFunds(
                $user,
                $request->amount,
                $request->description,
                $request->get('meta', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Funds deducted successfully',
                'new_balance' => $this->walletService->getBalance($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|string',
            'recipient_id' => 'required|integer',
            'amount' => config('wallet.validation.amount'),
            'description' => config('wallet.validation.description'),
        ]);

        try {
            $sender = $request->user();

            // Find recipient
            $recipient = app($request->recipient_type)->findOrFail($request->recipient_id);

            $success = $this->walletService->transferFunds(
                $sender,
                $recipient,
                $request->amount,
                $request->description,
                $request->get('meta', [])
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer completed successfully',
                    'sender_balance' => $this->walletService->getBalance($sender),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Transfer failed',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
