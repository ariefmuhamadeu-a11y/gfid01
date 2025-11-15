<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // 1️⃣ OWNER → selalu boleh masuk ke semua route
        if ($user->isOwner()) {
            return $next($request);
        }

        // 2️⃣ ADMIN → kalau route termasuk 'admin'
        if (in_array('admin', $roles) && $user->isAdmin()) {
            return $next($request);
        }

        // 3️⃣ User biasa → cek role normal
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // 4️⃣ Else → tolak
        abort(403, 'Anda tidak punya akses.');
    }
}
