<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGovernment
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'government', 403);
        return $next($request);
    }
}
