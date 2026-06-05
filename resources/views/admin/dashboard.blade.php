@extends('layouts.app')

@section('content')

<div class="app-page">
    <section class="page-container py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Admin Console</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">System overview</h1>
                <p class="mt-2 text-slate-600">Monitor users, conversations, and platform activity.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.users') }}" class="btn-secondary py-2">Users</a>
                <a href="{{ route('admin.chats') }}" class="btn-primary py-2">Chat History</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <div class="metric-card">
                <p class="text-sm font-semibold text-slate-500">Total users</p>
                <p class="mt-2 text-4xl font-extrabold text-slate-950">{{ $totalUsers }}</p>
            </div>
            <div class="metric-card">
                <p class="text-sm font-semibold text-slate-500">Total conversations</p>
                <p class="mt-2 text-4xl font-extrabold text-slate-950">{{ $totalChats }}</p>
            </div>
            <div class="metric-card">
                <p class="text-sm font-semibold text-slate-500">Chats today</p>
                <p class="mt-2 text-4xl font-extrabold text-slate-950">{{ $todayChats }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_420px]">
            <div class="section-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Last 7 days</h2>
                        <p class="mt-1 text-sm text-slate-500">Conversation volume by day</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($dailyChats as $day)
                        @php
                            $max = max($chartData->max() ?: 1, 1);
                            $width = max(8, round(($day->total / $max) * 100));
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($day->date)->format('M d') }}</span>
                                <span class="font-bold text-slate-900">{{ $day->total }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100">
                                <div class="h-3 rounded-full bg-blue-600" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                            No conversation activity yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="section-card p-6">
                <h2 class="text-lg font-bold text-slate-950">Top active users</h2>
                <div class="mt-5 space-y-3">
                    @forelse($topUsers as $index => $userStat)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-900">{{ $userStat->user->name ?? 'Unknown User' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $userStat->user->email ?? 'N/A' }}</p>
                            </div>
                            <span class="status-pill border-blue-200 bg-blue-50 text-blue-700">{{ $userStat->total }} chats</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                            No chat data available.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>

@endsection
