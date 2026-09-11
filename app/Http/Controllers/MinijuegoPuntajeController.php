<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\MinijuegoPuntaje;
use App\Models\Juego;
use App\Models\User;
use App\Models\Ciclo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MinijuegoPuntajeController extends Controller
{
    public function index()
    {
        // 1. Buscamos el ciclo que esté activo actualmente
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        $query = MinijuegoPuntaje::with(['user', 'juego']);

        // 2. Si hay un ciclo activo, filtramos los puntajes por sus fechas de inicio y fin
        if ($cicloActivo && $cicloActivo->fecha_inicio && $cicloActivo->fecha_fin) {
            $query->whereBetween('created_at', [
                Carbon::parse($cicloActivo->fecha_inicio)->startOfDay(),
                Carbon::parse($cicloActivo->fecha_fin)->endOfDay()
            ]);
        }

        // 3. Ordenamos de mayor a menor puntaje y el más reciente como desempate
        $puntajes = $query->orderBy('puntaje', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('minijuegos_puntajes.index', compact('puntajes', 'cicloActivo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'puntaje' => 'required|integer|min:0',
        ]);

        $juego = Juego::where('slug', 'dinosaurio_runner')->firstOrFail();

        // Usamos Auth::id() en lugar de auth()->id()
        $userId = Auth::id();

        MinijuegoPuntaje::create([
            'user_id'  => $userId,
            'juego_id' => $juego->id,
            'puntaje'  => $request->puntaje,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Puntaje guardado con éxito!'
        ]);
    }

    public function iniciarPartida(Request $request, string $slug)
    {
        $juego = Juego::where('slug', $slug)->firstOrFail();

        // Buscamos directamente con el ID usando la Facade Auth
        $user = User::with('wallet')->find(Auth::id());

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el usuario autenticado.'
            ], 401);
        }

        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una billetera asociada a tu usuario.'
            ], 400);
        }

        $costo = $juego->costo_ficha;

        if ($wallet->saldo_actual < $costo) {
            return response()->json([
                'success' => false,
                'message' => '¡No tienes suficientes moneditas! Necesitas ' . $costo . ' fichas para jugar.'
            ], 400);
        }

        $wallet->saldo_actual -= $costo;
        $wallet->save();

        return response()->json([
            'success' => true,
            'message' => '¡Ficha descontada con éxito! Que comience el juego.',
            'nuevo_saldo' => $wallet->saldo_actual
        ]);
    }
}
