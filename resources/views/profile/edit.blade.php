<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">Account</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Profile settings</h1>
        </div>
    </x-slot>

    <div class="app-page py-8">
        <div class="page-container grid gap-6 lg:grid-cols-[360px_1fr]">
            <aside class="section-card h-fit p-6">
                <div class="grid h-16 w-16 place-items-center rounded-2xl bg-blue-100 text-xl font-extrabold text-blue-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <h2 class="mt-4 text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-500">Credits</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-950">{{ $user->credit_points }}</p>
                </div>
            </aside>

            <div class="space-y-6">
                <div class="section-card p-6 sm:p-8">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="section-card p-6 sm:p-8">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="section-card border-red-200 p-6 sm:p-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
