<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SolanaBalanceService
{
    public function getBalance(string $publicKey): int
    {
        $mint = 'mytoken';

        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $publicKey)) {
            return 0;
        }

        return Cache::remember(
            'wallet_balance:' . $publicKey,
            300,
            fn () => $this->fetchFromRpc($publicKey, $mint)
        );
    }

    public function hasReduction(string $publicKey): bool
    {
        return $this->getBalance($publicKey) >= 1;
    }

    public function clearCache(string $publicKey): void
    {
        Cache::forget('wallet_balance:' . $publicKey);
    }

    public function fetchFromRpc(string $wallet, string $mint): int
    {
        $response = Http::timeout(10)
            ->retry(2, 100)
            ->post(config('services.helius.rpc_url'), [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getTokenAccountsByOwner',
                'params' => [
                    $wallet,
                    ['mint' => $mint],
                    ['encoding' => 'jsonParsed']
                ]
            ])
            ->throw()
            ->json();

        return collect($response['result']['value'] ?? [])
            ->sum(fn ($acc) => (int) $acc['account']['data']['parsed']['info']['tokenAmount']['amount']);
    }
}
