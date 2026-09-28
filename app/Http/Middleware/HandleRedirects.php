<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Redirects are an old-URL/SEO concept — they should only ever
        // apply to a page a browser navigates TO (GET/HEAD), never to
        // a form submission (POST/PUT/PATCH/DELETE). Without this
        // guard, a redirect rule whose from_path happens to match a
        // POST endpoint's path (e.g. an admin creates one for
        // "agency/billing/checkout" without realizing it's also a
        // real route) would silently intercept that submission before
        // it ever reached its controller — the request would 301/302
        // away with the POST body simply discarded, looking to the
        // user like the button did nothing at all.
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        $redirect = Redirect::active()->where('from_path', $path)->first();

        if ($redirect) {
            $redirect->increment('hit_count');

            $to = str_starts_with($redirect->to_path, 'http') ? $redirect->to_path : url($redirect->to_path);

            return redirect($to, $redirect->status_code);
        }

        return $next($request);
    }
}
