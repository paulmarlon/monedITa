<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
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
        // Forzar HTTPS en producción para evitar problemas de contenido mixto
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Inyectar la configuración globalmente en todas las vistas (para el title, logo, etc.)
        View::composer('*', function ($view) {
            $configGlobal = Configuracion::first();
            $view->with('configGlobal', $configGlobal);
        });

        // --- EVENTO LOGIN ---
        // --- EVENTO LOGIN ---
        Event::listen(Login::class, function (Login $event) {
            $request = app(Request::class);
            $userId = $event->user->getAuthIdentifier();

            // Obtenemos el ID de la sesión actual de forma segura
            $currentSessionId = $request->hasSession() ? $request->session()->getId() : null;

            // 1. Borramos sesiones anteriores del mismo usuario en el esquema integrador
            $query = DB::table('integrador.sessions')->where('user_id', $userId);

            if ($currentSessionId) {
                $query->where('id', '!=', $currentSessionId);
            }

            $query->delete();

            // 2. Inserta el registro permanente en la bitácora histórica del esquema integrador
            DB::table('integrador.auditoria_accesos')->insert([
                'user_id'    => $userId,
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->header('User-Agent'),
                'evento'     => 'LOGIN',
                'created_at' => now(),
            ]);
        });
    }
}
