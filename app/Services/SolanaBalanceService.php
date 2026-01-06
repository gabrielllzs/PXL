<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SolanaBalanceService
{
    public function getBalance(string $publicKey): int
    {
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $publicKey)) {
            return 0;
        }

        return Cache::remember(
            'wallet_balance:' . $publicKey,
            300,
            fn () => $this->fetchFromRpc($publicKey)
        );
    }

    public function hasReduction(string $publicKey): bool
    {
        return $this->getBalance($publicKey) >= 0;
    }

    public function clearCache(string $publicKey): void
    {
        Cache::forget('wallet_balance:' . $publicKey);
    }

    private function fetchFromRpc(string $publicKey): int
    {
        $response = Http::timeout(10)
            ->retry(2, 100)
            ->post(config('services.helius.rpc_url'), [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getBalance',
                'params' => [$publicKey]
            ])
            ->throw()
            ->json();

        return $response['result']['value'] ?? 0;
    }
}
