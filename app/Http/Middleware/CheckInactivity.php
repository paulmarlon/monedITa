<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckInactivity
{
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario está autenticado
        if (Auth::check()) {
            $inactiveTime = 300; // Tiempo en segundos (Ej: 900 segundos = 15 minutos)
            $lastActivity = session('last_activity');

            if ($lastActivity && (time() - $lastActivity > $inactiveTime)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('message', 'Tu sesión ha expirado por inactividad.');
            }

            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}
