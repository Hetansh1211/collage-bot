<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshCredits
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->refreshCreditsIfDue();
        }

        return $next($request);
    }
}
