<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\Configuracion;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inyectar la configuración globalmente en todas las vistas (para el title, logo, etc.)
        View::composer('*', function ($view) {
            $configGlobal = Configuracion::first();
            $view->with('configGlobal', $configGlobal);
        });

        // --- LO TUYO QUEDA INTACTO AQUÍ ABAJO ---
        Event::listen(Login::class, function (Login $event) {
            $request = request();
            $currentSessionId = $request->session()->getId();

            // 1. Borra de la tabla sessions cualquier otra sesión activa de este mismo usuario (Sesión única)
            DB::table('sessions')
                ->where('user_id', $event->user->getAuthIdentifier())
                ->where('id', '!=', $currentSessionId)
                ->delete();

            // 2. Inserta el registro permanente en la bitácora histórica de accesos
            DB::table('auditoria_accesos')->insert([
                'user_id' => $event->user->getAuthIdentifier(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'evento' => 'LOGIN',
                'created_at' => now(),
            ]);
        });
    }
}
