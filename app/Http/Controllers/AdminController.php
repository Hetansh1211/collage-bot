<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Admin Dashboard
    public function index()
{
    $totalUsers = User::count();
    $totalChats = Chat::count();
    $todayChats = Chat::whereDate('created_at', today())->count();

    $topUsers = Chat::select('user_id', DB::raw('count(*) as total'))
        ->groupBy('user_id')
        ->orderByDesc('total')
        ->with('user')
        ->limit(5)
        ->get();

    $dailyChats = Chat::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    $chartLabels = $dailyChats->pluck('date')->map(function ($date) {
        return $date;
    });
    $chartData = $dailyChats->pluck('total');
return view('admin.dashboard', compact(
    'totalUsers',
    'totalChats',
    'todayChats',
    'topUsers',
    'dailyChats',
    'chartLabels',
    'chartData'
));

}

    

    // Admin Users Page
    public function users()
    {
        $users = User::latest()->get();
        return view('admin.users', compact('users'));
    }

    // Admin Chats Page
    public function chats()
    {
        $chats = Chat::latest()->with('user')->get();
        return view('admin.chats', compact('chats'));
    }

    // Make user admin
    public function makeAdmin(User $user)
    {
        $user->update(['is_admin' => 1]);
        return redirect()->route('admin.users')->with('success', $user->name . ' is now an admin');
    }

    // Remove admin status
    public function removeAdmin(User $user)
    {
        $user->update(['is_admin' => 0]);
        return redirect()->route('admin.users')->with('success', $user->name . ' is no longer an admin');
    }
}
