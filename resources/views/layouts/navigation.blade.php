<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
    <div class="page-container">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-950 text-sm font-bold text-white shadow-sm">AI</span>
                    <span class="text-lg font-extrabold tracking-tight text-slate-950">College AI</span>
                </a>

                @auth
                    <div class="hidden items-center gap-1 md:flex">
                        <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('chat') }}" class="rounded-xl px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('chat*') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            Chat
                        </a>
                        <a href="{{ route('algo.index') }}" class="rounded-xl px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('algo*') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            Algo
                        </a>
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-3 py-2 text-sm font-semibold transition {{ request()->is('admin*') ? 'bg-slate-100 text-slate-950' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                                Admin
                            </a>
                        @endif
                    </div>
                @endauth
            </div>

            @auth
                <div class="hidden items-center gap-3 md:flex">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700" title="Refresh: {{ Auth::user()->nextCreditRefreshLabel() }}">
                        <div>{{ Auth::user()->credit_points }} credits</div>
                        <div class="text-[10px] font-medium text-slate-500">Refresh {{ Auth::user()->getNextRefreshTime()->format('h:i A') }}</div>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white py-1.5 pl-2 pr-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-blue-100 text-xs font-bold text-blue-700">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-32 truncate">{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

                            @if(auth()->user()->is_admin)
                                <x-dropdown-link :href="route('admin.dashboard')">Admin Panel</x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Log Out
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <button @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 md:hidden">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endauth

            @guest
                <div class="hidden items-center gap-3 md:flex">
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="btn-primary py-2">
                        Create Account
                    </a>
                </div>

                <button @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 md:hidden">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endguest
        </div>

        <div :class="{'block': open, 'hidden': !open}" class="hidden border-t border-slate-200 py-3 md:hidden">
            @auth
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard</a>
                    <a href="{{ route('chat') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Chat</a>
                    <a href="{{ route('algo.index') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Algo</a>
                    <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Profile</a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Admin Panel</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Log Out
                        </button>
                    </form>
                </div>
            @endauth

            @guest
                <div class="space-y-1">
                    <a href="{{ route('login') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Login</a>
                    <a href="{{ route('register') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Create Account</a>
                </div>
            @endguest
        </div>
    </div>
</nav>
