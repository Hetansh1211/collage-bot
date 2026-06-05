<x-guest-layout>
    <div>
        <p class="eyebrow">New password</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Choose a secure password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Use a password that is unique to your College AI account.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full">
            Reset Password
        </button>
    </form>
</x-guest-layout>
