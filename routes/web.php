<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
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


Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

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

    // Módulo de Ciclos
    Route::get('ciclos/trash', [CicloController::class, 'trash'])->name('ciclos.trash')->middleware('can:ver_ciclos');
    Route::patch('ciclos/{id}/restore', [CicloController::class, 'restore'])->name('ciclos.restore')->middleware('can:editar_ciclos');
    Route::resource('ciclos', CicloController::class);

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
    // Auditoría de Sesiones Activas e IPs
    Route::get('/admin/sesiones', [SessionAuditController::class, 'index'])->name('admin.sessions.index')->middleware('can:ver_sesiones_activas');
    Route::delete('/admin/sesiones/{id}', [SessionAuditController::class, 'destroy'])->name('admin.sessions.destroy')->middleware('can:expulsar_usuarios');
    Route::get('/admin/historial-accesos', [AccessLogController::class, 'index'])
        ->name('admin.historial.index')
        ->middleware('can:ver_historial_accesos');
});
Route::get('/probar-session-db', function () {
    return response()->json([
        'driver_real_en_render' => config('session.driver'),
        'tabla_real_en_render' => config('session.table'),
        'conexion_bd' => config('database.default'),
        'sesiones_en_public' => \Illuminate\Support\Facades\Schema::hasTable('sessions') ? DB::table('sessions')->count() : 'No existe tabla en public'
    ]);
});
