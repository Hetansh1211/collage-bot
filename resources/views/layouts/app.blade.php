<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>College Chatbot</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-900">

    @include('layouts.navigation')

    <main>
        @isset($header)
            <div class="border-b border-slate-200 bg-white">
                <div class="page-container py-6">
                    {{ $header }}
                </div>
            </div>
        @endisset

        @yield('content')

        @isset($slot)
            {{ $slot }}
        @endisset
    </main>

</body>
</html>
