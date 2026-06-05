@extends('layouts.app')

@section('content')

<div class="app-page">
    <section class="page-container py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Admin Console</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Chat history</h1>
                <p class="mt-2 text-slate-600">Review recent user questions and AI responses.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn-secondary py-2">Back to Dashboard</a>
        </div>

        <div class="mt-6 section-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-950">Conversations</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $chats->count() }} total chat messages</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($chats as $chat)
                    <article class="p-5 transition hover:bg-slate-50">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-950">{{ $chat->user->name ?? 'Deleted User' }}</p>
                                <p class="text-sm text-slate-500">{{ $chat->user->email ?? 'N/A' }}</p>
                            </div>
                            <div class="text-sm text-slate-500 md:text-right">
                                <p>{{ $chat->created_at->format('M d, Y') }}</p>
                                <p>{{ $chat->created_at->format('h:i A') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-blue-700">User message</p>
                                <p class="mt-2 text-sm leading-6 text-slate-700">{{ $chat->user_message }}</p>
                                @if($chat->user_attachment)
                                    <p class="mt-3 text-xs font-semibold text-slate-500">File: {{ basename($chat->user_attachment) }}</p>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">AI reply</p>
                                <p class="mt-2 max-h-36 overflow-hidden text-sm leading-6 text-slate-700">{{ $chat->bot_reply }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="p-12 text-center text-slate-500">
                        No chat history available.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>

@endsection
