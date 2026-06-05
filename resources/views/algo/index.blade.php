@extends('layouts.app')

@section('content')

@php
    $defaultSymbol = $instruments[0]['symbol'] ?? 'NIFTY50';
@endphp

<div class="app-page">
    <section class="page-container py-6 lg:py-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Algo Market System</p>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 md:text-4xl">India + MCX Strategy Desk</h1>
                <p class="mt-3 max-w-3xl leading-7 text-slate-600">
                    Paper-trading dashboard with live-ready prices, EMA/RSI/ATR signals, candlestick charts, and backtest results.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                <span id="feedStatus" class="rounded-xl bg-slate-950 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white">Connecting</span>
                <span id="marketClock" class="px-3 py-2 text-sm font-semibold text-slate-600">--:--:--</span>
            </div>
        </div>
    </section>

    <section class="page-container pb-4">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="section-card overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-slate-200 bg-white p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Instrument</span>
                            <select id="symbolSelect" class="form-input mt-1 py-2">
                                @foreach($instruments as $instrument)
                                    <option value="{{ $instrument['symbol'] }}" @selected($instrument['symbol'] === $defaultSymbol)>
                                        {{ $instrument['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Timeframe</span>
                            <select id="timeframeSelect" class="form-input mt-1 py-2">
                                <option value="1m">1 minute</option>
                                <option value="5m" selected>5 minutes</option>
                                <option value="15m">15 minutes</option>
                                <option value="1h">1 hour</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Range</span>
                            <select id="rangeSelect" class="form-input mt-1 py-2">
                                <option value="1d" selected>1 day</option>
                                <option value="5d">5 days</option>
                                <option value="1mo">1 month</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button id="refreshButton" type="button" class="btn-secondary px-4 py-2">Refresh</button>
                        <button id="autoButton" type="button" class="btn-primary px-4 py-2">Auto On</button>
                    </div>
                </div>

                <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_260px]">
                    <div class="min-h-[420px] p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-3">
                            <div>
                                <h2 id="chartTitle" class="text-xl font-extrabold text-slate-950">Loading</h2>
                                <p id="chartSubtitle" class="text-sm font-medium text-slate-500">Waiting for feed</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide">
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> EMA 9
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span> EMA 21
                                </span>
                            </div>
                        </div>
                        <div class="relative h-[360px] rounded-2xl border border-slate-200 bg-slate-950 p-2">
                            <canvas id="priceChart" class="h-full w-full"></canvas>
                            <div id="chartEmpty" class="absolute inset-0 hidden place-items-center text-sm font-semibold text-slate-300">No candles available</div>
                        </div>
                    </div>

                    <aside class="border-t border-slate-200 p-4 lg:border-l lg:border-t-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Current Signal</p>
                        <div id="signalBadge" class="mt-3 rounded-2xl bg-slate-100 px-4 py-5 text-center">
                            <p class="text-3xl font-extrabold text-slate-950">HOLD</p>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Loading</p>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold text-slate-500">Entry</p>
                                <p id="entryValue" class="mt-1 text-lg font-extrabold text-slate-950">--</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold text-slate-500">Confidence</p>
                                <p id="confidenceValue" class="mt-1 text-lg font-extrabold text-slate-950">--</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold text-slate-500">Stop</p>
                                <p id="stopValue" class="mt-1 text-lg font-extrabold text-red-600">--</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold text-slate-500">Target</p>
                                <p id="targetValue" class="mt-1 text-lg font-extrabold text-emerald-600">--</p>
                            </div>
                        </div>

                        <p id="signalReason" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">Strategy engine is loading.</p>
                    </aside>
                </div>
            </div>

            <aside class="section-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="eyebrow">Watchlist</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-950">Live Prices</h2>
                    </div>
                    <span id="watchlistCount" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">0</span>
                </div>
                <div id="watchlist" class="mt-4 space-y-2"></div>
            </aside>
        </div>
    </section>

    <section class="page-container pb-12">
        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1.1fr]">
            <div class="section-card p-5">
                <p class="eyebrow">Backtest</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Strategy Results</h2>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-slate-950 p-4 text-white">
                        <p class="text-xs text-slate-300">Net return</p>
                        <p id="netReturn" class="mt-1 text-2xl font-extrabold">--</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold text-slate-500">Win rate</p>
                        <p id="winRate" class="mt-1 text-2xl font-extrabold text-slate-950">--</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold text-slate-500">Trades</p>
                        <p id="tradeCount" class="mt-1 text-2xl font-extrabold text-slate-950">--</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold text-slate-500">Max drawdown</p>
                        <p id="drawdown" class="mt-1 text-2xl font-extrabold text-slate-950">--</p>
                    </div>
                </div>
            </div>

            <div class="section-card p-5">
                <p class="eyebrow">Indicators</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Market Filters</h2>
                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-sm font-semibold text-slate-600">EMA 9</span>
                        <span id="emaFastValue" class="text-lg font-extrabold text-slate-950">--</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-sm font-semibold text-slate-600">EMA 21</span>
                        <span id="emaSlowValue" class="text-lg font-extrabold text-slate-950">--</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-sm font-semibold text-slate-600">RSI 14</span>
                        <span id="rsiValue" class="text-lg font-extrabold text-slate-950">--</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="text-sm font-semibold text-slate-600">ATR 14</span>
                        <span id="atrValue" class="text-lg font-extrabold text-slate-950">--</span>
                    </div>
                </div>
            </div>

            <div class="section-card p-5">
                <p class="eyebrow">Paper Trades</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Recent Results</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-3 text-left">Side</th>
                                <th class="px-3 py-3 text-right">Entry</th>
                                <th class="px-3 py-3 text-right">Exit</th>
                                <th class="px-3 py-3 text-right">P&L</th>
                            </tr>
                        </thead>
                        <tbody id="tradeRows" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
(() => {
    const instruments = @json($instruments);
    const routes = {
        snapshot: @json(route('algo.snapshot')),
        history: @json(route('algo.history')),
        strategy: @json(route('algo.strategy')),
    };

    const state = {
        symbol: @json($defaultSymbol),
        interval: '5m',
        range: '1d',
        auto: true,
        bars: [],
        analysis: null,
        quotes: [],
    };

    const el = (id) => document.getElementById(id);
    const currency = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 });
    const time = new Intl.DateTimeFormat('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    function instrumentFor(symbol) {
        return instruments.find((instrument) => instrument.symbol === symbol) || instruments[0];
    }

    function formatValue(value) {
        if (value === null || value === undefined || Number.isNaN(value)) {
            return '--';
        }

        return currency.format(Number(value));
    }

    function setClock() {
        el('marketClock').textContent = `${time.format(new Date())} IST`;
    }

    function params() {
        return new URLSearchParams({
            symbol: state.symbol,
            interval: state.interval,
            range: state.range,
        });
    }

    async function getJson(url) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        return response.json();
    }

    async function loadSnapshot() {
        const payload = await getJson(routes.snapshot);
        state.quotes = payload.data || [];
        renderWatchlist();
        updateFeedStatus();
    }

    async function loadChart() {
        const [history, strategy] = await Promise.all([
            getJson(`${routes.history}?${params()}`),
            getJson(`${routes.strategy}?${params()}`),
        ]);

        state.bars = history.bars || [];
        state.analysis = strategy.analysis || null;
        renderChartMeta();
        renderStrategy();
        drawChart();
    }

    async function refreshAll() {
        try {
            await Promise.all([loadSnapshot(), loadChart()]);
        } catch (error) {
            el('feedStatus').textContent = 'Feed Error';
            el('feedStatus').className = 'rounded-xl bg-red-600 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white';
        }
    }

    function updateFeedStatus() {
        const quote = state.quotes.find((item) => item.symbol === state.symbol);
        const paperCount = state.quotes.filter((item) => item.mode === 'paper').length;
        const label = quote ? `${quote.source}` : 'Connected';
        el('feedStatus').textContent = paperCount > 0 ? `${label} + Paper` : label;
        el('feedStatus').className = quote && quote.mode === 'paper'
            ? 'rounded-xl bg-amber-600 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white'
            : 'rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white';
    }

    function renderWatchlist() {
        el('watchlistCount').textContent = state.quotes.length;
        el('watchlist').innerHTML = state.quotes.map((quote) => {
            const positive = quote.change >= 0;
            const active = quote.symbol === state.symbol;

            return `
                <button type="button" data-symbol="${quote.symbol}" class="watch-item block w-full rounded-2xl border ${active ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white'} p-3 text-left transition hover:border-blue-300 hover:bg-blue-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-950">${quote.name}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">${quote.market} · ${quote.source}</p>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs font-bold ${quote.mode === 'paper' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">${quote.mode}</span>
                    </div>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <p class="text-xl font-extrabold text-slate-950">${formatValue(quote.price)}</p>
                        <p class="text-sm font-bold ${positive ? 'text-emerald-600' : 'text-red-600'}">${positive ? '+' : ''}${formatValue(quote.change)} (${positive ? '+' : ''}${quote.changePercent}%)</p>
                    </div>
                </button>
            `;
        }).join('');

        document.querySelectorAll('.watch-item').forEach((button) => {
            button.addEventListener('click', () => {
                state.symbol = button.dataset.symbol;
                el('symbolSelect').value = state.symbol;
                refreshAll();
            });
        });
    }

    function renderChartMeta() {
        const instrument = instrumentFor(state.symbol);
        const quote = state.quotes.find((item) => item.symbol === state.symbol);
        el('chartTitle').textContent = instrument.name;
        el('chartSubtitle').textContent = quote
            ? `${instrument.market} · ${formatValue(quote.price)} · ${quote.source}`
            : instrument.market;
    }

    function renderStrategy() {
        const analysis = state.analysis;

        if (!analysis) {
            return;
        }

        const signalClasses = {
            BUY: 'mt-3 rounded-2xl bg-emerald-50 px-4 py-5 text-center ring-1 ring-emerald-200',
            SELL: 'mt-3 rounded-2xl bg-red-50 px-4 py-5 text-center ring-1 ring-red-200',
            HOLD: 'mt-3 rounded-2xl bg-slate-100 px-4 py-5 text-center ring-1 ring-slate-200',
        };
        const signalTextClasses = {
            BUY: 'text-3xl font-extrabold text-emerald-700',
            SELL: 'text-3xl font-extrabold text-red-700',
            HOLD: 'text-3xl font-extrabold text-slate-950',
        };

        el('signalBadge').className = signalClasses[analysis.signal] || signalClasses.HOLD;
        el('signalBadge').innerHTML = `<p class="${signalTextClasses[analysis.signal] || signalTextClasses.HOLD}">${analysis.signal}</p><p class="mt-1 text-sm font-semibold text-slate-500">${analysis.bias}</p>`;
        el('entryValue').textContent = formatValue(analysis.entry);
        el('confidenceValue').textContent = `${analysis.confidence}%`;
        el('stopValue').textContent = formatValue(analysis.stopLoss);
        el('targetValue').textContent = formatValue(analysis.target);
        el('signalReason').textContent = analysis.reason;
        el('emaFastValue').textContent = formatValue(analysis.indicators.emaFast);
        el('emaSlowValue').textContent = formatValue(analysis.indicators.emaSlow);
        el('rsiValue').textContent = formatValue(analysis.indicators.rsi);
        el('atrValue').textContent = formatValue(analysis.indicators.atr);
        el('netReturn').textContent = `${analysis.backtest.netReturn}%`;
        el('winRate').textContent = `${analysis.backtest.winRate}%`;
        el('tradeCount').textContent = analysis.backtest.trades;
        el('drawdown').textContent = `${analysis.backtest.maxDrawdown}%`;
        el('tradeRows').innerHTML = analysis.backtest.recentTrades.length
            ? analysis.backtest.recentTrades.map((trade) => `
                <tr>
                    <td class="px-3 py-3 font-bold ${trade.side === 'BUY' ? 'text-emerald-600' : 'text-red-600'}">${trade.side}</td>
                    <td class="px-3 py-3 text-right font-semibold text-slate-700">${formatValue(trade.entry)}</td>
                    <td class="px-3 py-3 text-right font-semibold text-slate-700">${formatValue(trade.exit)}</td>
                    <td class="px-3 py-3 text-right font-bold ${trade.pnl >= 0 ? 'text-emerald-600' : 'text-red-600'}">${formatValue(trade.pnl)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" class="px-3 py-6 text-center text-sm font-semibold text-slate-500">No closed paper trades yet</td></tr>';
    }

    function drawChart() {
        const canvas = el('priceChart');
        const empty = el('chartEmpty');
        const bars = state.bars || [];
        const analysis = state.analysis;

        if (!canvas || bars.length === 0) {
            empty.classList.remove('hidden');
            empty.classList.add('grid');
            return;
        }

        empty.classList.add('hidden');
        empty.classList.remove('grid');

        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;

        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, rect.width, rect.height);

        const padding = { top: 18, right: 54, bottom: 28, left: 10 };
        const width = rect.width - padding.left - padding.right;
        const height = rect.height - padding.top - padding.bottom;
        const values = bars.flatMap((bar) => [bar.high, bar.low]);
        const emaFast = analysis?.series?.emaFast || [];
        const emaSlow = analysis?.series?.emaSlow || [];
        [...emaFast, ...emaSlow].forEach((value) => {
            if (value !== null) values.push(value);
        });

        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = Math.max(max - min, 1);
        const xStep = width / Math.max(bars.length - 1, 1);
        const candleWidth = Math.max(4, Math.min(12, xStep * 0.62));
        const y = (value) => padding.top + ((max - value) / range) * height;
        const x = (index) => padding.left + (index * xStep);

        ctx.fillStyle = '#020617';
        ctx.fillRect(0, 0, rect.width, rect.height);
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.16)';
        ctx.lineWidth = 1;
        ctx.font = '11px Figtree, sans-serif';
        ctx.fillStyle = '#94a3b8';

        for (let i = 0; i <= 4; i++) {
            const yy = padding.top + (height / 4) * i;
            const value = max - (range / 4) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, yy);
            ctx.lineTo(rect.width - padding.right + 6, yy);
            ctx.stroke();
            ctx.fillText(formatValue(value), rect.width - padding.right + 12, yy + 4);
        }

        bars.forEach((bar, index) => {
            const xx = x(index);
            const openY = y(bar.open);
            const closeY = y(bar.close);
            const highY = y(bar.high);
            const lowY = y(bar.low);
            const up = bar.close >= bar.open;

            ctx.strokeStyle = up ? '#34d399' : '#fb7185';
            ctx.fillStyle = up ? '#34d399' : '#fb7185';
            ctx.beginPath();
            ctx.moveTo(xx, highY);
            ctx.lineTo(xx, lowY);
            ctx.stroke();
            ctx.fillRect(xx - candleWidth / 2, Math.min(openY, closeY), candleWidth, Math.max(Math.abs(closeY - openY), 2));
        });

        drawLine(ctx, emaFast, x, y, '#10b981');
        drawLine(ctx, emaSlow, x, y, '#f59e0b');

        const firstTime = new Date(bars[0].time);
        const lastTime = new Date(bars[bars.length - 1].time);
        ctx.fillStyle = '#94a3b8';
        ctx.fillText(time.format(firstTime), padding.left, rect.height - 8);
        ctx.fillText(time.format(lastTime), rect.width - padding.right - 34, rect.height - 8);
    }

    function drawLine(ctx, values, x, y, color) {
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        let started = false;

        values.forEach((value, index) => {
            if (value === null || value === undefined) {
                return;
            }

            if (!started) {
                ctx.moveTo(x(index), y(value));
                started = true;
            } else {
                ctx.lineTo(x(index), y(value));
            }
        });

        ctx.stroke();
    }

    el('symbolSelect').addEventListener('change', (event) => {
        state.symbol = event.target.value;
        refreshAll();
    });

    el('timeframeSelect').addEventListener('change', (event) => {
        state.interval = event.target.value;
        refreshAll();
    });

    el('rangeSelect').addEventListener('change', (event) => {
        state.range = event.target.value;
        refreshAll();
    });

    el('refreshButton').addEventListener('click', refreshAll);
    el('autoButton').addEventListener('click', () => {
        state.auto = !state.auto;
        el('autoButton').textContent = state.auto ? 'Auto On' : 'Auto Off';
        el('autoButton').className = state.auto ? 'btn-primary px-4 py-2' : 'btn-secondary px-4 py-2';
    });

    window.addEventListener('resize', drawChart);
    setClock();
    setInterval(setClock, 1000);
    refreshAll();
    setInterval(() => {
        if (state.auto) {
            loadSnapshot().then(loadChart).catch(() => {});
        }
    }, 12000);
})();
</script>

@endsection
