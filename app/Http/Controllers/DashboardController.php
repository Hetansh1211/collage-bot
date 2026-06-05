<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; // ✅ THIS LINE FIXES ERROR

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('dashboard', [
            'totalChats' => Chat::where('user_id', $user->id)->count(),
            'todayChats' => Chat::where('user_id', $user->id)
                                ->whereDate('created_at', today())
                                ->count(),
        ]);
    }
}