<?php

namespace App\Services;

class AlgoStrategyService
{
    /**
     * @param  array<int, array<string, mixed>>  $bars
     * @return array<string, mixed>
     */
    public function analyse(array $bars): array
    {
        $closes = array_map(fn (array $bar) => (float) $bar['close'], $bars);
        $emaFast = $this->ema($closes, 9);
        $emaSlow = $this->ema($closes, 21);
        $rsi = $this->rsi($closes, 14);
        $atr = $this->atr($bars, 14);
        $lastIndex = count($bars) - 1;
        $lastClose = $closes[$lastIndex] ?? 0.0;
        $lastAtr = $atr[$lastIndex] ?? max($lastClose * 0.01, 1);
        $signal = $this->signalAt($lastIndex, $closes, $emaFast, $emaSlow, $rsi);
        $risk = $this->riskPlan($signal, $lastClose, $lastAtr);
        $backtest = $this->backtest($bars, $emaFast, $emaSlow, $rsi, $atr);

        return [
            'signal' => $signal['side'],
            'bias' => $signal['bias'],
            'confidence' => $signal['confidence'],
            'reason' => $signal['reason'],
            'entry' => round($lastClose, 2),
            'stopLoss' => $risk['stopLoss'],
            'target' => $risk['target'],
            'riskReward' => $risk['riskReward'],
            'indicators' => [
                'emaFast' => round($emaFast[$lastIndex] ?? $lastClose, 2),
                'emaSlow' => round($emaSlow[$lastIndex] ?? $lastClose, 2),
                'rsi' => round($rsi[$lastIndex] ?? 50, 2),
                'atr' => round($lastAtr, 2),
            ],
            'series' => [
                'emaFast' => array_map(fn (?float $value) => $value === null ? null : round($value, 2), $emaFast),
                'emaSlow' => array_map(fn (?float $value) => $value === null ? null : round($value, 2), $emaSlow),
                'rsi' => array_map(fn (?float $value) => $value === null ? null : round($value, 2), $rsi),
            ],
            'backtest' => $backtest,
        ];
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float|null>
     */
    private function ema(array $values, int $period): array
    {
        $ema = [];
        $multiplier = 2 / ($period + 1);
        $previous = null;

        foreach ($values as $index => $value) {
            if ($index < $period - 1) {
                $ema[] = null;
                continue;
            }

            if ($previous === null) {
                $previous = array_sum(array_slice($values, $index - $period + 1, $period)) / $period;
            } else {
                $previous = (($value - $previous) * $multiplier) + $previous;
            }

            $ema[] = $previous;
        }

        return $ema;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float|null>
     */
    private function rsi(array $values, int $period): array
    {
        $rsi = array_fill(0, count($values), null);
        $gains = 0.0;
        $losses = 0.0;

        for ($i = 1; $i <= $period && $i < count($values); $i++) {
            $change = $values[$i] - $values[$i - 1];
            $gains += max($change, 0);
            $losses += abs(min($change, 0));
        }

        if (count($values) <= $period) {
            return $rsi;
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;
        $rsi[$period] = $this->rsiValue($avgGain, $avgLoss);

        for ($i = $period + 1; $i < count($values); $i++) {
            $change = $values[$i] - $values[$i - 1];
            $avgGain = (($avgGain * ($period - 1)) + max($change, 0)) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + abs(min($change, 0))) / $period;
            $rsi[$i] = $this->rsiValue($avgGain, $avgLoss);
        }

        return $rsi;
    }

    private function rsiValue(float $avgGain, float $avgLoss): float
    {
        if ($avgLoss === 0.0) {
            return 100.0;
        }

        return 100 - (100 / (1 + ($avgGain / $avgLoss)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $bars
     * @return array<int, float|null>
     */
    private function atr(array $bars, int $period): array
    {
        $trueRanges = [];
        $atr = array_fill(0, count($bars), null);

        foreach ($bars as $index => $bar) {
            $high = (float) $bar['high'];
            $low = (float) $bar['low'];
            $previousClose = $index > 0 ? (float) $bars[$index - 1]['close'] : (float) $bar['close'];
            $trueRanges[] = max($high - $low, abs($high - $previousClose), abs($low - $previousClose));
        }

        for ($i = $period - 1; $i < count($trueRanges); $i++) {
            if ($i === $period - 1) {
                $atr[$i] = array_sum(array_slice($trueRanges, 0, $period)) / $period;
                continue;
            }

            $atr[$i] = (($atr[$i - 1] * ($period - 1)) + $trueRanges[$i]) / $period;
        }

        return $atr;
    }

    /**
     * @param  array<int, float>  $closes
     * @param  array<int, float|null>  $emaFast
     * @param  array<int, float|null>  $emaSlow
     * @param  array<int, float|null>  $rsi
     * @return array<string, mixed>
     */
    private function signalAt(int $index, array $closes, array $emaFast, array $emaSlow, array $rsi): array
    {
        $fast = $emaFast[$index] ?? null;
        $slow = $emaSlow[$index] ?? null;
        $momentum = $rsi[$index] ?? 50;
        $close = $closes[$index] ?? 0;

        if ($fast === null || $slow === null) {
            return [
                'side' => 'HOLD',
                'bias' => 'Warming up',
                'confidence' => 0,
                'reason' => 'Need more candles before the strategy can score the setup.',
            ];
        }

        $trendSpread = $slow !== 0.0 ? abs($fast - $slow) / $slow : 0.0;
        $confidence = min(96, (int) round(45 + ($trendSpread * 2400) + abs($momentum - 50)));

        if ($close > $fast && $fast > $slow && $momentum >= 52 && $momentum <= 76) {
            return [
                'side' => 'BUY',
                'bias' => 'Bullish trend',
                'confidence' => $confidence,
                'reason' => 'Price is above EMA 9 and EMA 21 while RSI confirms positive momentum.',
            ];
        }

        if ($close < $fast && $fast < $slow && $momentum <= 48 && $momentum >= 24) {
            return [
                'side' => 'SELL',
                'bias' => 'Bearish trend',
                'confidence' => $confidence,
                'reason' => 'Price is below EMA 9 and EMA 21 while RSI confirms downside momentum.',
            ];
        }

        return [
            'side' => 'HOLD',
            'bias' => $fast >= $slow ? 'Neutral bullish' : 'Neutral bearish',
            'confidence' => max(30, min(72, $confidence - 18)),
            'reason' => 'Trend and momentum are not aligned strongly enough for a fresh entry.',
        ];
    }

    /**
     * @return array<string, float|null>
     */
    private function riskPlan(array $signal, float $entry, float $atr): array
    {
        if ($signal['side'] === 'BUY') {
            $stopLoss = $entry - (1.5 * $atr);
            $target = $entry + (2.25 * $atr);
        } elseif ($signal['side'] === 'SELL') {
            $stopLoss = $entry + (1.5 * $atr);
            $target = $entry - (2.25 * $atr);
        } else {
            return [
                'stopLoss' => null,
                'target' => null,
                'riskReward' => null,
            ];
        }

        return [
            'stopLoss' => round($stopLoss, 2),
            'target' => round($target, 2),
            'riskReward' => 1.5,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $bars
     * @param  array<int, float|null>  $emaFast
     * @param  array<int, float|null>  $emaSlow
     * @param  array<int, float|null>  $rsi
     * @param  array<int, float|null>  $atr
     * @return array<string, mixed>
     */
    private function backtest(array $bars, array $emaFast, array $emaSlow, array $rsi, array $atr): array
    {
        $position = null;
        $trades = [];
        $equity = 100000.0;
        $peak = $equity;
        $maxDrawdown = 0.0;
        $grossProfit = 0.0;
        $grossLoss = 0.0;

        for ($i = 25; $i < count($bars); $i++) {
            $closes = array_column($bars, 'close');
            $signal = $this->signalAt($i, array_map('floatval', $closes), $emaFast, $emaSlow, $rsi);
            $price = (float) $bars[$i]['close'];
            $barAtr = $atr[$i] ?? max($price * 0.01, 1);

            if ($position !== null) {
                $exit = false;

                if ($position['side'] === 'BUY') {
                    $exit = $price <= $position['stop'] || $price >= $position['target'] || $signal['side'] === 'SELL';
                    $pnl = $price - $position['entry'];
                } else {
                    $exit = $price >= $position['stop'] || $price <= $position['target'] || $signal['side'] === 'BUY';
                    $pnl = $position['entry'] - $price;
                }

                if ($exit) {
                    $returnPercent = ($pnl / $position['entry']) * 100;
                    $equity *= 1 + ($returnPercent / 100);
                    $peak = max($peak, $equity);
                    $maxDrawdown = max($maxDrawdown, (($peak - $equity) / $peak) * 100);

                    if ($pnl >= 0) {
                        $grossProfit += $pnl;
                    } else {
                        $grossLoss += abs($pnl);
                    }

                    $trades[] = [
                        'side' => $position['side'],
                        'entry' => round($position['entry'], 2),
                        'exit' => round($price, 2),
                        'pnl' => round($pnl, 2),
                        'returnPercent' => round($returnPercent, 2),
                        'time' => $bars[$i]['time'],
                    ];

                    $position = null;
                }
            }

            if ($position === null && in_array($signal['side'], ['BUY', 'SELL'], true)) {
                $position = [
                    'side' => $signal['side'],
                    'entry' => $price,
                    'stop' => $signal['side'] === 'BUY' ? $price - (1.5 * $barAtr) : $price + (1.5 * $barAtr),
                    'target' => $signal['side'] === 'BUY' ? $price + (2.25 * $barAtr) : $price - (2.25 * $barAtr),
                ];
            }
        }

        $wins = count(array_filter($trades, fn (array $trade) => $trade['pnl'] >= 0));
        $losses = max(count($trades) - $wins, 0);

        return [
            'trades' => count($trades),
            'wins' => $wins,
            'losses' => $losses,
            'winRate' => count($trades) > 0 ? round(($wins / count($trades)) * 100, 1) : 0,
            'netReturn' => round((($equity - 100000) / 100000) * 100, 2),
            'maxDrawdown' => round($maxDrawdown, 2),
            'profitFactor' => $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : ($grossProfit > 0 ? 99 : 0),
            'recentTrades' => array_slice(array_reverse($trades), 0, 6),
        ];
    }
}
