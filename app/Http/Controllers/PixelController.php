<?php

namespace App\Http\Controllers;

use App\Events\PixelClaimed;
use Illuminate\Http\Request;
use App\Models\Pixel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class PixelController extends Controller
{
    private const TOKEN_MINT = '8badswKtVajg5L1nHKCabkCwoFXBwK1CfuEMbQ8Vpump';
    private const GRID_MAX = 499;

    public function index(): JsonResponse
    {
        return response()->json(Pixel::all());
    }

    public function claim(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request);

        $txSignature = $data['tx_signature'];
        $x = (int)$data['x'];
        $y = (int)$data['y'];
        $color = $data['color'];
        $buyer = strtolower($data['buyer']);

        if ($this->isPixelTaken($x, $y)) {
            return response()->json(['message' => 'Pixel already taken'], 409);
        }

        if ($this->isTxUsed($txSignature)) {
            return response()->json(['message' => 'Transaction already used'], 409);
        }

        if (!$this->verifyTransaction($txSignature, $buyer)) {
            return response()->json(['message' => 'Transaction verification failed'], 400);
        }

        $pixel = $this->createPixel($x, $y, $color, $txSignature, $buyer);

        broadcast(new PixelClaimed($pixel))->toOthers();

        return response()->json($pixel, 201);
    }

    protected function validateRequest(Request $request): array
    {
        $rules = [
            'tx_signature' => 'required|string',
            'x' => 'required|integer|min:0|max:' . self::GRID_MAX,
            'y' => 'required|integer|min:0|max:' . self::GRID_MAX,
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buyer' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            abort(response()->json(['errors' => $validator->errors()], 422));
        }

        return $validator->validated();
    }

    protected function isPixelTaken(int $x, int $y): bool
    {
        return Pixel::where('x', $x)->where('y', $y)->exists();
    }

    protected function isTxUsed(string $signature): bool
    {
        return Pixel::where('tx_signature', $signature)->exists();
    }

    protected function createPixel(int $x, int $y, string $color, string $txSignature, string $buyer): Pixel
    {
        return Pixel::create([
            'x' => $x,
            'y' => $y,
            'color' => $color,
            'tx_signature' => $txSignature,
            'buyer_address' => $buyer,
        ]);
    }

    protected function verifyTransaction(string $signature, string $buyer): bool
    {
        $rpcUrl = env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com');

        try {
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
        } catch (\Throwable $e) {
            return false;
        }

        if (!$response->successful()) {
            return false;
        }

        $data = $response->json();

        $tx = $data['result'] ?? null;
        if (empty($tx)) {
            return false;
        }

        $balances = $tx['meta']['postTokenBalances'] ?? [];

        foreach ($balances as $balance) {
            $mint = $balance['mint'] ?? null;
            $owner = strtolower($balance['owner'] ?? '');
            $uiToken = $balance['uiTokenAmount'] ?? [];

            // Try uiAmount first, fallback to uiAmountString
            $amount = 0.0;
            if (isset($uiToken['uiAmount'])) {
                $amount = (float)$uiToken['uiAmount'];
            } elseif (!empty($uiToken['uiAmountString'])) {
                $amount = (float)$uiToken['uiAmountString'];
            }

            if ($mint === self::TOKEN_MINT && $owner === strtolower($buyer) && $amount >= 1.0) {
                return true;
            }
        }

        return false;
    }
}
