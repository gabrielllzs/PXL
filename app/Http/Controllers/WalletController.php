<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolanaBalanceService;
use Illuminate\Support\Facades\Log;
use StephenHill\Base58;

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
            'publicKey' => 'required|string|min:32|max:44',
            'signature' => 'required|array',
            'message' => 'required|string'
        ]);

        $publicKey = $validated['publicKey'];
        $signature = $validated['signature'];
        $message = $validated['message'];

        $base58 = new Base58();
        $publicKeyBytes = $base58->decode($publicKey);

        $signatureBytes = implode(array_map("chr", $signature));

        $isValid = sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKeyBytes);

        if (!$isValid) {
            Log::warning('Invalid wallet signature', ['publicKey' => $validated['publicKey']]);
            return response()->json([
                'signatureValid' => false,
                'error' => 'Invalid signature'
            ], 401);
        }

        $balance = $this->balanceService->getBalance($publicKey);
        $hasReduction = $this->balanceService->hasReduction($publicKey);

        
        return response()->json([
            'hasReduction' => $hasReduction,
            'balance' => $balance,
            'signatureValid' => true
        ]);
    }

}
