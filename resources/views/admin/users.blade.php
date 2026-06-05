@extends('layouts.app')

@section('content')

<div class="app-page">
    <section class="page-container py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Admin Console</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Users</h1>
                <p class="mt-2 text-slate-600">Review accounts and manage admin access.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn-secondary py-2">Back to Dashboard</a>
        </div>

        @if(session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 section-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="font-bold text-slate-950">Registered users</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $users->count() }} total accounts</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Credits</th>
                            <th>Joined</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            <tr class="hover:bg-slate-50">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->is_admin)
                                        <span class="status-pill border-red-200 bg-red-50 text-red-700">Admin</span>
                                    @else
                                        <span class="status-pill border-blue-200 bg-blue-50 text-blue-700">User</span>
                                    @endif
                                </td>
                                <td class="font-semibold text-slate-900">{{ $user->credit_points }}</td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        @if(!$user->is_admin)
                                            <form action="{{ route('admin.user.makeAdmin', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                                    Make Admin
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.user.removeAdmin', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                                    Remove Admin
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

@endsection
