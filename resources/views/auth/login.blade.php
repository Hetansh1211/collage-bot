<x-guest-layout>
    <div>
        <p class="eyebrow">Welcome back</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Login to College AI</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Continue your college guidance conversations and saved study plans.
        </p>
    </div>

    <x-auth-session-status class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                        Forgot password?
                    </a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-3 text-sm font-medium text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
            <span>Remember this device</span>
        </label>

        <button type="submit" class="btn-primary w-full">
            Login
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        New to College AI?
        <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:text-blue-900">Create an account</a>
    </p>
</x-guest-layout>
