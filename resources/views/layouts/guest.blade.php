<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'College Chatbot') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="app-page min-h-screen">
            <div class="page-container grid min-h-screen items-center gap-10 py-10 lg:grid-cols-[1fr_460px]">
                <section class="hidden lg:block">
                    <a href="/" class="inline-flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-950 text-sm font-bold text-white">AI</span>
                        <span class="text-xl font-extrabold tracking-tight text-slate-950">College AI</span>
                    </a>

                    <div class="mt-14 max-w-2xl">
                        <p class="eyebrow">Student support workspace</p>
                        <h1 class="mt-4 text-5xl font-extrabold leading-tight tracking-tight text-slate-950">
                            Clear answers for admissions, courses, and study planning.
                        </h1>
                        <p class="mt-5 text-lg leading-8 text-slate-600">
                            Sign in to continue conversations, upload academic files, and keep guidance organized by topic.
                        </p>
                    </div>

                    <div class="mt-10 grid max-w-2xl grid-cols-3 gap-3">
                        <div class="section-card p-4">
                            <p class="text-2xl font-bold text-slate-950">300</p>
                            <p class="mt-1 text-sm text-slate-500">Daily credits</p>
                        </div>
                        <div class="section-card p-4">
                            <p class="text-2xl font-bold text-slate-950">PDF</p>
                            <p class="mt-1 text-sm text-slate-500">File analysis</p>
                        </div>
                        <div class="section-card p-4">
                            <p class="text-2xl font-bold text-slate-950">24/7</p>
                            <p class="mt-1 text-sm text-slate-500">Study help</p>
                        </div>
                    </div>
                </section>

                <section class="section-card mx-auto w-full max-w-md p-6 sm:p-8">
                    <div class="mb-7 lg:hidden">
                        <a href="/" class="inline-flex items-center gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-950 text-sm font-bold text-white">AI</span>
                            <span class="text-lg font-extrabold tracking-tight text-slate-950">College AI</span>
                        </a>
                    </div>

                    {{ $slot }}
                </section>
            </div>
        </div>
    </body>
</html>
