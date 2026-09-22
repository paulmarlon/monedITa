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

            // 1. Opcional si pides contraseña en el login, pero para sesión única en BD con el driver database:
            // Laravel invalida las demás sesiones automáticamente si usas el middleware 'auth.session'.

            // 2. Registramos la bitácora histórica en integrador.auditoria_accesos
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
