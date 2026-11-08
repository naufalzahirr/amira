<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Pastikan user sudah login dan punya flag is_admin = true.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            // Bisa diarahkan ke halaman utama / 403
            return redirect()->route('login')
                ->with('error', 'Anda tidak memiliki akses ke halaman admin.');
        }

        return $next($request);
    }
}
