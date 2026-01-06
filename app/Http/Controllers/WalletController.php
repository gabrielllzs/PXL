<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolanaBalanceService;

class WalletController extends Controller
{
    private SolanaBalanceService $balanceService;

    public function __construct(SolanaBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function checkBalance(Request $request)
    {
        $validated = $request->validate([
            'publicKey' => 'required|string|min:32|max:44'
        ]);

        $publicKey = $validated['publicKey'];

        $balance = $this->balanceService->getBalance($publicKey);
        $hasReduction = $this->balanceService->hasReduction($publicKey);

        return response()->json([
            'hasReduction' => $hasReduction,
            'balance' => $balance
        ]);
    }
}
