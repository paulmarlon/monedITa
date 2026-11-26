<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CicloController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JuegoController;
use App\Http\Controllers\MinijuegoPuntajeController;
use App\Http\Controllers\SessionAuditController;
use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\UsuarioCicloController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['verify' => true]);

// Flujo 2FA en Web (sin sesión autenticada hasta verificar el código)
Route::middleware('web')->group(function () {
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.challenge');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
    Route::post('/2fa/cancel', [TwoFactorController::class, 'cancel'])->name('2fa.cancel');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Rutas protegidas globalmente para usuarios autenticados Y control de sesión única
Route::middleware(['auth', 'auth.session'])->group(function () {

    // Panel de Información / Estadísticas (Única declaración limpia)
    Route::get('/informacion', DashboardController::class)->name('dashboard.info');

    // Rutas de Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Configuración
    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index')->middleware('can:ver_configuracion');
    Route::put('configuracion/{configuracion}', [ConfiguracionController::class, 'update'])->name('configuracion.update')->middleware('can:editar_configuracion');

    // Transición limpia de Ciclos: redirige /ciclos al panel unificado y restringe rutas legacy a ADMINISTRADOR
    Route::get('ciclos', fn() => redirect()->route('admin.usuarios-ciclos.index'))
        ->name('ciclos.index')
        ->middleware('role:ADMINISTRADOR|SUPERVISOR');

    Route::middleware('role:ADMINISTRADOR')->prefix('ciclos')->name('ciclos.')->group(function () {
        Route::get('trash', [CicloController::class, 'trash'])->name('trash');
        Route::patch('{id}/restore', [CicloController::class, 'restore'])->name('restore');
        Route::get('create', [CicloController::class, 'create'])->name('create');
        Route::post('/', [CicloController::class, 'store'])->name('store');
        Route::get('{ciclo}', [CicloController::class, 'show'])->name('show');
        Route::get('{ciclo}/edit', [CicloController::class, 'edit'])->name('edit');
        Route::put('{ciclo}', [CicloController::class, 'update'])->name('update');
        Route::delete('{ciclo}', [CicloController::class, 'destroy'])->name('destroy');
    });

    // Módulo de Teams / Equipos
    Route::get('teams/trash', [TeamController::class, 'trash'])->name('teams.trash')->middleware('can:ver_teams');
    Route::patch('teams/{id}/restore', [TeamController::class, 'restore'])->name('teams.restore')->middleware('can:editar_teams');
    Route::resource('teams', TeamController::class);

    // Módulo de Roles (Spatie)
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions')->middleware('can:ver_roles');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.updatePermissions')->middleware('can:editar_roles');

    // Módulo de Wallet / Monedero
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallets.index')->middleware('can:ver_wallet');
    Route::post('/wallet/transferir', [WalletController::class, 'transferir'])->name('wallets.transferir')->middleware('can:transferir_wallet');
    Route::post('/admin/wallet/acreditar', [WalletController::class, 'acreditar'])->name('wallets.acreditar')->middleware('can:acreditar_wallet');

    // Módulo de Juegos
    Route::resource('juegos', JuegoController::class);
    Route::get('juegos-trash', [JuegoController::class, 'trash'])->name('juegos.trash')->middleware('can:ver_juegos');
    Route::post('juegos-restore/{id}', [JuegoController::class, 'restore'])->name('juegos.restore')->middleware('can:editar_juegos');
    Route::delete('juegos-force-delete/{id}', [JuegoController::class, 'forceDelete'])->name('juegos.forceDelete')->middleware('can:eliminar_juegos');

    // Puntajes y Minijuegos
    Route::get('minijuegos-puntajes', [MinijuegoPuntajeController::class, 'index'])->name('minijuegos-puntajes.index')->middleware('can:ver_puntajes');
    Route::get('minijuegos/dino', function () {
        return view('minijuegos.dino');
    })->name('juegos.dino')->middleware('can:ver_dino');

    Route::post('minijuegos/{slug}/iniciar', [MinijuegoPuntajeController::class, 'iniciarPartida'])->name('minijuegos.iniciar');
    Route::post('minijuegos/guardar-puntaje', [MinijuegoPuntajeController::class, 'store'])->name('minijuegos.guardar');

    // Auditoría de Sesiones Activas e IPs
    Route::get('/admin/sesiones', [SessionAuditController::class, 'index'])->name('admin.sessions.index')->middleware('can:ver_sesiones_activas');
    Route::delete('/admin/sesiones/{id}', [SessionAuditController::class, 'destroy'])->name('admin.sessions.destroy')->middleware('can:expulsar_usuarios');
    Route::get('/admin/historial-accesos', [AccessLogController::class, 'index'])
        ->name('admin.historial.index')
        ->middleware('can:ver_historial_accesos');

    // Control de Usuarios y Ciclos (ADMINISTRADOR y SUPERVISOR pueden ver/asignar según jerarquía; cerrar/abrir/papelera/restaurar solo ADMINISTRADOR)
    Route::middleware('role:ADMINISTRADOR|SUPERVISOR')
        ->prefix('admin/usuarios-ciclos')
        ->name('admin.usuarios-ciclos.')
        ->group(function () {
            Route::get('/', [UsuarioCicloController::class, 'index'])->name('index');
            Route::post('/usuarios/{user}/asignar', [UsuarioCicloController::class, 'asignarRol'])->name('asignar');

            Route::middleware('role:ADMINISTRADOR')->group(function () {
                Route::post('/ciclos/cerrar', [UsuarioCicloController::class, 'cerrarCiclo'])->name('cerrar-ciclo');
                Route::post('/ciclos/nuevo', [UsuarioCicloController::class, 'nuevoPeriodo'])->name('nuevo-periodo');
                Route::delete('/ciclos/{ciclo}/papelera', [UsuarioCicloController::class, 'enviarPapelera'])->name('ciclos.papelera');
                Route::patch('/ciclos/{id}/restaurar', [UsuarioCicloController::class, 'restaurar'])->name('ciclos.restaurar');
            });
        });
});
