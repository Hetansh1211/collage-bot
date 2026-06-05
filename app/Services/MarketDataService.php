<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MarketDataService
{
    /**
     * Exchange-grade Indian/MCX feeds normally require a broker or data-vendor key.
     * These instruments are live-ready and fall back to deterministic paper data.
     *
     * @return array<string, array<string, mixed>>
     */
    public function instruments(): array
    {
        return [
            'NIFTY50' => [
                'symbol' => 'NIFTY50',
                'name' => 'NIFTY 50',
                'market' => 'NSE Index',
                'yahoo' => '^NSEI',
                'base' => 24750,
                'currency' => 'pts',
                'tick' => 0.05,
            ],
            'BANKNIFTY' => [
                'symbol' => 'BANKNIFTY',
                'name' => 'NIFTY Bank',
                'market' => 'NSE Index',
                'yahoo' => '^NSEBANK',
                'base' => 53200,
                'currency' => 'pts',
                'tick' => 0.05,
            ],
            'RELIANCE' => [
                'symbol' => 'RELIANCE',
                'name' => 'Reliance Industries',
                'market' => 'NSE Equity',
                'yahoo' => 'RELIANCE.NS',
                'base' => 2910,
                'currency' => 'INR',
                'tick' => 0.05,
            ],
            'TCS' => [
                'symbol' => 'TCS',
                'name' => 'Tata Consultancy Services',
                'market' => 'NSE Equity',
                'yahoo' => 'TCS.NS',
                'base' => 3860,
                'currency' => 'INR',
                'tick' => 0.05,
            ],
            'HDFCBANK' => [
                'symbol' => 'HDFCBANK',
                'name' => 'HDFC Bank',
                'market' => 'NSE Equity',
                'yahoo' => 'HDFCBANK.NS',
                'base' => 1665,
                'currency' => 'INR',
                'tick' => 0.05,
            ],
            'INFY' => [
                'symbol' => 'INFY',
                'name' => 'Infosys',
                'market' => 'NSE Equity',
                'yahoo' => 'INFY.NS',
                'base' => 1515,
                'currency' => 'INR',
                'tick' => 0.05,
            ],
            'MCXGOLD' => [
                'symbol' => 'MCXGOLD',
                'name' => 'MCX Gold',
                'market' => 'MCX Commodity',
                'yahoo' => null,
                'base' => 98500,
                'currency' => 'INR',
                'tick' => 1,
            ],
            'MCXSILVER' => [
                'symbol' => 'MCXSILVER',
                'name' => 'MCX Silver',
                'market' => 'MCX Commodity',
                'yahoo' => null,
                'base' => 111000,
                'currency' => 'INR',
                'tick' => 1,
            ],
            'MCXCRUDE' => [
                'symbol' => 'MCXCRUDE',
                'name' => 'MCX Crude Oil',
                'market' => 'MCX Commodity',
                'yahoo' => null,
                'base' => 6750,
                'currency' => 'INR',
                'tick' => 1,
            ],
            'MCXNG' => [
                'symbol' => 'MCXNG',
                'name' => 'MCX Natural Gas',
                'market' => 'MCX Commodity',
                'yahoo' => null,
                'base' => 305,
                'currency' => 'INR',
                'tick' => 0.1,
            ],
            'MCXCOPPER' => [
                'symbol' => 'MCXCOPPER',
                'name' => 'MCX Copper',
                'market' => 'MCX Commodity',
                'yahoo' => null,
                'base' => 875,
                'currency' => 'INR',
                'tick' => 0.05,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function snapshot(): array
    {
        return collect($this->instruments())
            ->map(fn (array $instrument) => $this->quote($instrument['symbol']))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(string $symbol): array
    {
        $instrument = $this->instrument($symbol);
        $bars = $this->history($symbol, '1d', '5m', 80);
        $last = end($bars);
        $previous = $bars[count($bars) - 2] ?? $last;
        $change = $last['close'] - $previous['close'];
        $changePercent = $previous['close'] !== 0.0 ? ($change / $previous['close']) * 100 : 0;

        return [
            'symbol' => $instrument['symbol'],
            'name' => $instrument['name'],
            'market' => $instrument['market'],
            'price' => $this->roundToTick($last['close'], $instrument['tick']),
            'change' => round($change, 2),
            'changePercent' => round($changePercent, 2),
            'high' => $this->roundToTick(max(array_column($bars, 'high')), $instrument['tick']),
            'low' => $this->roundToTick(min(array_column($bars, 'low')), $instrument['tick']),
            'volume' => (int) array_sum(array_column($bars, 'volume')),
            'currency' => $instrument['currency'],
            'source' => $last['source'],
            'mode' => $last['mode'],
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(string $symbol, string $range = '1d', string $interval = '5m', int $points = 160): array
    {
        $instrument = $this->instrument($symbol);
        $range = in_array($range, ['1d', '5d', '1mo'], true) ? $range : '1d';
        $interval = in_array($interval, ['1m', '5m', '15m', '1h'], true) ? $interval : '5m';
        $cacheKey = 'market-history-'.Str::slug($symbol.'-'.$range.'-'.$interval);

        return Cache::remember($cacheKey, now()->addSeconds(8), function () use ($instrument, $range, $interval, $points) {
            $bars = $this->historyFromYahoo($instrument, $range, $interval);

            if (count($bars) < 30) {
                return $this->syntheticBars($instrument, $interval, $points);
            }

            return array_slice($bars, -$points);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function instrument(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        $instruments = $this->instruments();

        abort_unless(isset($instruments[$symbol]), 404, 'Unknown instrument.');

        return $instruments[$symbol];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function historyFromYahoo(array $instrument, string $range, string $interval): array
    {
        if (empty($instrument['yahoo'])) {
            return [];
        }

        try {
            $url = 'https://query1.finance.yahoo.com/v8/finance/chart/'.rawurlencode($instrument['yahoo']);
            $response = Http::timeout(3)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'CollegeAlgoDashboard/1.0'])
                ->get($url, [
                    'range' => $range,
                    'interval' => $interval,
                    'includePrePost' => 'false',
                ]);

            if (! $response->successful()) {
                return [];
            }

            $result = $response->json('chart.result.0');

            if (! is_array($result)) {
                return [];
            }

            $timestamps = $result['timestamp'] ?? [];
            $quote = $result['indicators']['quote'][0] ?? [];
            $bars = [];

            foreach ($timestamps as $index => $timestamp) {
                $close = $quote['close'][$index] ?? null;

                if ($close === null) {
                    continue;
                }

                $open = $quote['open'][$index] ?? $close;
                $high = $quote['high'][$index] ?? max($open, $close);
                $low = $quote['low'][$index] ?? min($open, $close);

                $bars[] = [
                    'time' => $timestamp * 1000,
                    'open' => round((float) $open, 2),
                    'high' => round((float) max($high, $open, $close), 2),
                    'low' => round((float) min($low, $open, $close), 2),
                    'close' => round((float) $close, 2),
                    'volume' => (int) ($quote['volume'][$index] ?? 0),
                    'source' => 'Yahoo delayed',
                    'mode' => 'delayed',
                ];
            }

            return $bars;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function syntheticBars(array $instrument, string $interval, int $points): array
    {
        $stepSeconds = match ($interval) {
            '1m' => 60,
            '15m' => 900,
            '1h' => 3600,
            default => 300,
        };

        $seed = abs(crc32($instrument['symbol']));
        $base = (float) $instrument['base'];
        $bars = [];
        $now = intdiv(time(), $stepSeconds) * $stepSeconds;
        $previousClose = $base;

        for ($i = $points - 1; $i >= 0; $i--) {
            $slot = $now - ($i * $stepSeconds);
            $waveA = sin(($slot / $stepSeconds + ($seed % 97)) / 8.5);
            $waveB = cos(($slot / $stepSeconds + ($seed % 53)) / 19.0);
            $trend = (($slot / $stepSeconds + ($seed % 31)) % 70) / 70 - 0.5;
            $close = $base * (1 + ($waveA * 0.006) + ($waveB * 0.003) + ($trend * 0.004));
            $open = $previousClose;
            $spread = max($base * 0.0012, (float) $instrument['tick'] * 6);
            $high = max($open, $close) + abs(sin($slot / 177 + $seed)) * $spread;
            $low = min($open, $close) - abs(cos($slot / 191 + $seed)) * $spread;

            $bars[] = [
                'time' => $slot * 1000,
                'open' => $this->roundToTick($open, $instrument['tick']),
                'high' => $this->roundToTick($high, $instrument['tick']),
                'low' => $this->roundToTick($low, $instrument['tick']),
                'close' => $this->roundToTick($close, $instrument['tick']),
                'volume' => (int) (10000 + (($seed + $slot) % 90000)),
                'source' => 'Local paper feed',
                'mode' => 'paper',
            ];

            $previousClose = $close;
        }

        return $bars;
    }

    private function roundToTick(float $value, float $tick): float
    {
        if ($tick <= 0) {
            return round($value, 2);
        }

        $rounded = round($value / $tick) * $tick;

        return round($rounded, $tick >= 1 ? 0 : 2);
    }
}
