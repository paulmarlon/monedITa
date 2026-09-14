<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <--- Importante para las transacciones SQL
use App\Models\MinijuegoPuntaje;
use App\Models\Juego;
use App\Models\User;
use App\Models\Ciclo;
use App\Models\Transaccion; // <--- Importamos el modelo Transaccion
use Carbon\Carbon;
use Illuminate\Http\Request;

class MinijuegoPuntajeController extends Controller
{
    public function index()
    {
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        $query = MinijuegoPuntaje::with(['user', 'juego']);

        if ($cicloActivo && $cicloActivo->fecha_inicio && $cicloActivo->fecha_fin) {
            $query->whereBetween('created_at', [
                Carbon::parse($cicloActivo->fecha_inicio)->startOfDay(),
                Carbon::parse($cicloActivo->fecha_fin)->endOfDay()
            ]);
        }

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
        $user = User::with('wallet')->find(Auth::id());

        if (!$user || !$user->wallet) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una billetera asociada a tu usuario.'
            ], 400);
        }

        $wallet = $user->wallet;
        $costo = $juego->costo_ficha;

        if ($wallet->saldo_actual < $costo) {
            return response()->json([
                'success' => false,
                'message' => '¡No tienes suficientes moneditas! Necesitas ' . $costo . ' fichas para jugar.'
            ], 400);
        }

        // Buscamos el ciclo activo
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();
        $cicloId = $cicloActivo ? $cicloActivo->id : 1;

        // Ejecutamos el descuento registrando la transacción hacia el Administrador (ID 1)
        DB::transaction(function () use ($user, $wallet, $costo, $juego, $cicloId) {

            // 1. Descontamos el saldo actual de la wallet del estudiante
            $wallet->saldo_actual -= $costo;
            $wallet->save();

            // 2. Creamos la transacción en el libro mayor (emisor: estudiante, receptor: Admin ID 1)
            Transaccion::create([
                'ciclo_id'       => $cicloId,
                'emisor_id'      => $user->id,
                'receptor_id'    => 1, // ID del Administrador o cuenta del sistema
                'monto'          => $costo,
                'tipo_operacion' => 'CONSUMO_MINIJUEGO',
                'observacion'    => 'Consumo de fichas para partida de: ' . ($juego->nombre ?? 'Minijuego')
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => '¡Ficha descontada con éxito! Que comience el juego.',
            'nuevo_saldo' => $wallet->saldo_actual
        ]);
    }
}
