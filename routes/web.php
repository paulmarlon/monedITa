<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CicloController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionController, App\Http\Controllers\ProfileController, App\Http\Controllers\JuegoController, App\Http\Controllers\MinijuegoPuntajeController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Rutas protegidas globalmente para usuarios autenticados
Route::middleware(['auth'])->group(function () {
    // ==========================================
    // PANEL DE INFORMACIÓN / ESTADÍSTICAS (¡AQUÍ VA!)
    // ==========================================
    Route::get('/informacion', DashboardController::class)->name('dashboard.info');

    // Rutas de Perfil (¡ESTO ERA LO QUE FALTABA!)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Configuración
    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index')->middleware('can:ver_configuracion');
    Route::put('configuracion/{configuracion}', [ConfiguracionController::class, 'update'])->name('configuracion.update')->middleware('can:editar_configuracion');

    // Módulo de Ciclos (Trash + CRUD protegido por sus respectivos permisos)
    Route::get('ciclos/trash', [CicloController::class, 'trash'])->name('ciclos.trash')->middleware('can:ver_ciclos');
    Route::patch('ciclos/{id}/restore', [CicloController::class, 'restore'])->name('ciclos.restore')->middleware('can:editar_ciclos');

    Route::resource('ciclos', CicloController::class)->middleware([
        'index'   => 'can:ver_ciclos',
        'create'  => 'can:crear_ciclos',
        'store'   => 'can:crear_ciclos',
        'show'    => 'can:ver_ciclos',
        'edit'    => 'can:editar_ciclos',
        'update'  => 'can:editar_ciclos',
        'destroy' => 'can:eliminar_ciclos',
    ]);

    // Módulo de Teams / Equipos (Trash + CRUD protegido por sus respectivos permisos)
    Route::get('teams/trash', [TeamController::class, 'trash'])->name('teams.trash')->middleware('can:ver_teams');
    Route::patch('teams/{id}/restore', [TeamController::class, 'restore'])->name('teams.restore')->middleware('can:editar_teams');

    Route::resource('teams', TeamController::class)->middleware([
        'index'   => 'can:ver_teams',
        'create'  => 'can:crear_teams',
        'store'   => 'can:crear_teams',
        'show'    => 'can:ver_teams',
        'edit'    => 'can:editar_teams',
        'update'  => 'can:editar_teams',
        'destroy' => 'can:eliminar_teams',
    ]);

    // Módulo de Roles (Spatie) + Rutas personalizadas de Permisos
    Route::resource('roles', RoleController::class)->middleware([
        'index'   => 'can:ver_roles',
        'create'  => 'can:crear_roles',
        'store'   => 'can:crear_roles',
        'show'    => 'can:ver_roles',
        'edit'    => 'can:editar_roles',
        'update'  => 'can:editar_roles',
        'destroy' => 'can:eliminar_roles',
    ]);

    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions')->middleware('can:ver_roles');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.updatePermissions')->middleware('can:editar_roles');

    // Módulo de Wallet / Monedero
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallets.index')->middleware('can:ver_wallet');
    Route::post('/wallet/transferir', [WalletController::class, 'transferir'])->name('wallets.transferir')->middleware('can:transferir_wallet');
    Route::post('/admin/wallet/acreditar', [WalletController::class, 'acreditar'])->name('wallets.acreditar')->middleware('can:acreditar_wallet');

    Route::resource('juegos', JuegoController::class)->middleware([
        'index'   => 'can:ver_juegos',
        'create'  => 'can:crear_juegos',
        'store'   => 'can:crear_juegos',
        'show'    => 'can:ver_juegos',
        'edit'    => 'can:editar_juegos',
        'update'  => 'can:editar_juegos',
        'destroy' => 'can:eliminar_juegos',
    ]);
    // Rutas de la Papelera protegidas con los permisos adecuados
    Route::get('juegos-trash', [JuegoController::class, 'trash'])
        ->name('juegos.trash')
        ->middleware('can:ver_juegos'); // O un permiso específico como 'ver_papelera_juegos' si lo prefieres

    Route::post('juegos-restore/{id}', [JuegoController::class, 'restore'])
        ->name('juegos.restore')
        ->middleware('can:editar_juegos'); // O 'restaurar_juegos'

    Route::delete('juegos-force-delete/{id}', [JuegoController::class, 'forceDelete'])
        ->name('juegos.forceDelete')
        ->middleware('can:eliminar_juegos');
    // Rutas de los puntajes (Cambiado a 'ver_puntajes' para que el estudiante también pueda entrar)
    Route::get('minijuegos-puntajes', [MinijuegoPuntajeController::class, 'index'])
        ->name('minijuegos-puntajes.index')
        ->middleware('can:ver_puntajes');

    // Vista para jugar al Dinosaurio (Protegido con el permiso 'ver_dino' del seeder)
    Route::get('minijuegos/dino', function () {
        return view('minijuegos.dino');
    })->name('juegos.dino')
        ->middleware('can:ver_dino');

    // Ruta para descontar la ficha en la billetera al iniciar
    Route::post('minijuegos/{slug}/iniciar', [MinijuegoPuntajeController::class, 'iniciarPartida'])
        ->name('minijuegos.iniciar')
        ->middleware('auth');

    // Ruta para guardar el puntaje vía AJAX al terminar la partida
    Route::post('minijuegos/guardar-puntaje', [MinijuegoPuntajeController::class, 'store'])
        ->name('minijuegos.guardar')
        ->middleware('auth');
});
