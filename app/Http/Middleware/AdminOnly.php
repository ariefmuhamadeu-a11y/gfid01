<?php

// app/Http/Middleware/AdminOnly.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isOwner()) {
            abort(403, 'Hanya owner yang boleh mengakses.');
        }

        return $next($request);
    }
}
