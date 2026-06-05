<x-guest-layout>
    <div>
        <p class="eyebrow">Security check</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Confirm your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Please confirm your password before continuing to this secure area.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full">
            Confirm
        </button>
    </form>
</x-guest-layout>
