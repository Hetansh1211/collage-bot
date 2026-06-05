<?php

namespace App\Http\Controllers;

use App\Services\AlgoStrategyService;
use App\Services\MarketDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlgoController extends Controller
{
    public function __construct(
        private readonly MarketDataService $marketData,
        private readonly AlgoStrategyService $strategy,
    ) {
    }

    public function index(): View
    {
        return view('algo.index', [
            'instruments' => array_values($this->marketData->instruments()),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        return response()->json([
            'data' => $this->marketData->snapshot(),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $symbol = strtoupper((string) $request->query('symbol', 'NIFTY50'));
        $range = (string) $request->query('range', '1d');
        $interval = (string) $request->query('interval', '5m');

        return response()->json([
            'symbol' => $symbol,
            'bars' => $this->marketData->history($symbol, $range, $interval),
        ]);
    }

    public function strategy(Request $request): JsonResponse
    {
        $symbol = strtoupper((string) $request->query('symbol', 'NIFTY50'));
        $range = (string) $request->query('range', '1d');
        $interval = (string) $request->query('interval', '5m');
        $bars = $this->marketData->history($symbol, $range, $interval);

        return response()->json([
            'symbol' => $symbol,
            'analysis' => $this->strategy->analyse($bars),
        ]);
    }
}
