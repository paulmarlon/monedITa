<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CicloController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Módulo de Ciclos (CRUD completo + Papelera independiente)
Route::get('ciclos/trash', [CicloController::class, 'trash'])->name('ciclos.trash');
Route::patch('ciclos/{id}/restore', [CicloController::class, 'restore'])->name('ciclos.restore');
Route::resource('ciclos', CicloController::class);
