<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (Auth::check()) {

            // 👉 If already logged in → go to admin dashboard
            return redirect('/admin');

            // OR if normal dashboard:
            // return redirect('/dashboard');
        }

        return $next($request);
    }
}