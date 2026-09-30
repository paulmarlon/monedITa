<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MinijuegoApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::post('/juegos/guardar-puntaje', [MinijuegoApiController::class, 'guardarPuntaje']);
    Route::post('/juegos/{slug}/iniciar', [MinijuegoApiController::class, 'iniciar'])
        ->middleware('throttle:30,1');
});
