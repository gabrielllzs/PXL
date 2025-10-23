<?php

namespace App\Http\Controllers;

use App\Events\PixelClaimed;
use Illuminate\Http\Request;
use App\Models\Pixel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PixelController extends Controller
{
    // SPL token mint address
    protected $tokenMint = 'DG1Sos2qR8Ut7c2JRsNGydt99NNV5VKuSjZNbjXepump';

    public function index()
    {
        $pixels = Pixel::all();
        return response()->json($pixels);
    }
    public function claim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tx_signature' => 'required|string',
            'x' => 'required|integer|min:0|max:499', // Changed from 999 to 499
            'y' => 'required|integer|min:0|max:499', // Changed from 999 to 499
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buyer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $txSignature = $request->input('tx_signature');
        $x = (int)$request->input('x');
        $y = (int)$request->input('y');
        $color = $request->input('color');
        $buyer = strtolower($request->input('buyer'));

        if (Pixel::where('x', $x)->where('y', $y)->exists()) {
            return response()->json(['message' => 'Pixel already taken'], 409);
        }

        if (Pixel::where('tx_signature', $txSignature)->exists()) {
            return response()->json(['message' => 'Transaction already used'], 409);
        }

        if (!$this->verifyTransaction($txSignature, $buyer)) {
            return response()->json(['message' => 'Transaction verification failed'], 400);
        }

        $pixel = Pixel::create([
            'x' => $x,
            'y' => $y,
            'color' => $color,
            'tx_signature' => $txSignature,
            'buyer_address' => $buyer,
        ]);

        broadcast(new PixelClaimed($pixel))->toOthers();

        return response()->json($pixel, 201);
    }

    protected function verifyTransaction(string $signature, string $buyer): bool
    {
        $rpcUrl = env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com');

        // Post request to Solana RPC
        $response = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTransaction',
            'params' => [
                $signature,
                [
                    'encoding' => 'jsonParsed',
                    'maxSupportedTransactionVersion' => 0
                ]
            ],
        ]);


        $data = $response->json();

        // Log full response for debugging
        \Log::info("Solana TX Response for {$signature}:", $data);

        if (empty($data['result'])) {
            \Log::warning("Transaction not found: {$signature}");
            return false;
        }

        $tx = $data['result'];

        // Get post-transaction token balances
        $balances = $tx['meta']['postTokenBalances'] ?? [];

        foreach ($balances as $balance) {
            $mint = $balance['mint'] ?? null;
            $owner = strtolower($balance['owner'] ?? '');
            $amount = (float)($balance['uiTokenAmount']['uiAmount'] ?? 0);

            \Log::info("Checking token balance: mint={$mint}, owner={$owner}, amount={$amount}");

            if ($mint === $this->tokenMint && $owner === strtolower($buyer) && $amount >= 1) {
                return true;
            }
        }

        \Log::warning("Transaction verification failed for buyer {$buyer}, signature {$signature}");
        return false;
    }

}
