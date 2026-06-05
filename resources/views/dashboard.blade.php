@extends('layouts.app')

@section('content')

@php
    $quickPromptPool = [
        'Suggest best colleges for computer science.',
        'Compare BCA vs B.Tech for software career.',
        'Summarize this admission PDF for me.',
        'Create a 6-month roadmap for learning web development.',
        'Top scholarships for engineering students in India.',
        'How to prepare for first-year CS semester effectively?',
        'Best colleges with strong AI and machine learning programs.',
        'Difference between data science and software engineering careers.',
        'Build a weekly study plan for entrance exam preparation.',
        'How to make a strong Statement of Purpose (SOP)?',
        'Explain hostels, campus life, and student clubs in top colleges.',
        'What skills should I learn in first year to get internships?',
    ];
    shuffle($quickPromptPool);
    $quickPrompts = array_slice($quickPromptPool, 0, 4);
@endphp

<div class="app-page">
    <section class="page-container py-8 lg:py-10">
        <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
            <div class="section-card overflow-hidden">
                <div class="border-b border-slate-200 bg-white px-6 py-6">
                    <p class="eyebrow">Dashboard</p>
                    <div class="mt-3 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 md:text-4xl">
                                Welcome back, {{ Auth::user()->name }}
                            </h1>
                            <p class="mt-3 max-w-2xl leading-7 text-slate-600">
                                Start a focused college conversation, review documents, or continue planning from previous chats.
                            </p>
                        </div>
                        <a href="{{ route('chat') }}" class="btn-primary">Open Chat</a>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-950 p-5 text-white">
                        <p class="text-sm text-slate-300">Available credits</p>
                        <p class="mt-2 text-4xl font-extrabold">{{ Auth::user()->credit_points }}</p>
                        <p class="mt-2 text-xs text-slate-400">30 credits per AI response</p>
                        <p class="mt-1 text-xs text-slate-400">Refresh: {{ Auth::user()->nextCreditRefreshLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-sm font-semibold text-slate-600">Supported work</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950">Text + Files</p>
                        <p class="mt-2 text-xs text-slate-500">Ask questions and attach supported files.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-blue-50 p-5">
                        <p class="text-sm font-semibold text-blue-700">Best for</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950">College decisions</p>
                        <p class="mt-2 text-xs text-slate-600">Admissions, courses, studies, and career paths.</p>
                    </div>
                    <a href="{{ route('algo.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 transition hover:border-emerald-300 hover:bg-emerald-100">
                        <p class="text-sm font-semibold text-emerald-700">Market lab</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950">Algo system</p>
                        <p class="mt-2 text-xs text-slate-600">Live-ready charts, strategy signals, and paper results.</p>
                    </a>
                </div>
            </div>

            <aside class="section-card p-6">
                <p class="eyebrow">Quick start</p>
                <h2 class="mt-3 text-xl font-bold text-slate-950">Try a prompt</h2>
                <div class="mt-5 space-y-3">
                    @foreach($quickPrompts as $prompt)
                        <a href="{{ route('chat', ['prompt' => $prompt]) }}" class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium leading-6 text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">
                            {{ $prompt }}
                        </a>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>

    <section class="page-container pb-12">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="section-card p-5">
                <p class="text-sm font-semibold text-slate-500">Ask</p>
                <h3 class="mt-2 font-bold text-slate-950">Admissions and eligibility</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Get concise guidance with next steps and documents to check.</p>
            </div>
            <div class="section-card p-5">
                <p class="text-sm font-semibold text-slate-500">Compare</p>
                <h3 class="mt-2 font-bold text-slate-950">Courses and careers</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Understand tradeoffs between degrees, skills, and career tracks.</p>
            </div>
            <div class="section-card p-5">
                <p class="text-sm font-semibold text-slate-500">Review</p>
                <h3 class="mt-2 font-bold text-slate-950">Academic files</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Summarize notices, PDFs, screenshots, and student notes.</p>
            </div>
            <div class="section-card p-5">
                <p class="text-sm font-semibold text-slate-500">Organize</p>
                <h3 class="mt-2 font-bold text-slate-950">Conversation history</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Pin important chats and search them when you return.</p>
            </div>
        </div>
    </section>
</div>

@endsection
