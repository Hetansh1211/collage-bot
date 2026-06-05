<x-guest-layout>
    <div>
        <p class="eyebrow">Account recovery</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Reset your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Enter your email and we will send you a password reset link.
        </p>
    </div>

    <x-auth-session-status class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full">
            Send Reset Link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:text-blue-900">Back to login</a>
    </p>
</x-guest-layout>
