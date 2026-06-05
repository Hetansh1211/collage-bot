<x-guest-layout>
    <div>
        <p class="eyebrow">Verify email</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Check your inbox</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            We sent a verification link to your email address. Verify your account to continue.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            A new verification link has been sent.
        </div>
    @endif

    <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary">
                Resend Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">
                Log Out
            </button>
        </form>
    </div>
</x-guest-layout>
