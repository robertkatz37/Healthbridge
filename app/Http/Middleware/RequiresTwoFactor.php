<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('auth.2fa.user_id')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
