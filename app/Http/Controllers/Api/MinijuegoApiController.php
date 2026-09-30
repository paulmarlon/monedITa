<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use App\Models\Juego;
use App\Models\MinijuegoPuntaje;
use App\Models\Transaccion;
use App\Models\Wallet;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MinijuegoApiController extends Controller
{
    private const ADMIN_ID = 1;
    private const PARTIDA_TTL_MINUTOS = 120;
    private const PUNTAJE_MAXIMO = 10_000_000;

    /**
     * POST /api/juegos/{slug}/iniciar
     */
    public function iniciar(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        // SoftDeletes ya excluye los juegos eliminados
        $juego = Juego::where('slug', $slug)->where('activo', true)->first();

        if (! $juego) {
            return $this->error('Juego no encontrado o inactivo.', 404);
        }

        $ciclo = Ciclo::vigente();

        if (! $ciclo) {
            return $this->error('No hay un ciclo académico activo.', 409);
        }

        try {
            $resultado = DB::transaction(function () use ($user, $juego, $ciclo) {
                // Bloqueo de fila: serializa peticiones simultáneas del mismo usuario
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

                if (! $wallet) {
                    throw new DomainException('El usuario no tiene billetera.', 404);
                }

                // Aritmética decimal exacta (strings con 2 decimales)
                $costo = number_format((float) $juego->costo_ficha, 2, '.', '');
                $saldo = number_format((float) $wallet->saldo_actual, 2, '.', '');

                if (bccomp($saldo, $costo, 2) < 0) {
                    throw new DomainException('Saldo insuficiente para iniciar la partida.', 402);
                }

                $nuevoSaldo = bcsub($saldo, $costo, 2);

                $wallet->saldo_actual = $nuevoSaldo;
                $wallet->save();

                Transaccion::create([
                    'ciclo_id'       => $ciclo->id,
                    'emisor_id'      => $user->id,
                    'receptor_id'    => self::ADMIN_ID,
                    'monto'          => $costo,
                    'tipo_operacion' => 'CONSUMO_MINIJUEGO',
                    'observacion'    => "Partida de {$juego->titulo}",
                ]);

                return ['saldo' => $nuevoSaldo, 'costo' => $costo];
            });
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 422);
        } catch (Throwable $e) {
            report($e);
            return $this->error('No se pudo iniciar la partida. Intenta nuevamente.', 500);
        }

        // Partida pagada, de un solo uso
        $partidaId = (string) Str::uuid();

        Cache::put(
            $this->partidaKey($user->id, $juego->id, $partidaId),
            now()->timestamp,
            now()->addMinutes(self::PARTIDA_TTL_MINUTOS)
        );

        return response()->json([
            'success'       => true,
            'message'       => 'Partida iniciada.',
            'partida_id'    => $partidaId,
            'fichas_usadas' => (float) $resultado['costo'],
            'nuevo_saldo'   => (float) $resultado['saldo'],
        ]);
    }

    /**
     * POST /api/juegos/guardar-puntaje
     */
    public function guardarPuntaje(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'       => ['required', 'string'],
            'puntaje'    => ['required', 'integer', 'min:0', 'max:' . self::PUNTAJE_MAXIMO],
            'partida_id' => ['required', 'uuid'],
        ]);

        $user  = $request->user();
        $juego = Juego::where('slug', $data['slug'])->first();

        if (! $juego) {
            return $this->error('Juego no encontrado.', 404);
        }

        $key = $this->partidaKey($user->id, $juego->id, $data['partida_id']);

        // pull = leer y borrar: cada partida pagada permite un solo puntaje
        if (! Cache::pull($key)) {
            return $this->error('Partida inválida, expirada o ya registrada.', 403);
        }

        try {
            $registro = MinijuegoPuntaje::create([
                'user_id'  => $user->id,
                'juego_id' => $juego->id,
                'puntaje'  => $data['puntaje'],
            ]);

            $mejor = (int) MinijuegoPuntaje::where('user_id', $user->id)
                ->where('juego_id', $juego->id)
                ->max('puntaje');
        } catch (Throwable $e) {
            report($e);
            // Devolver la partida para que el cliente pueda reintentar
            Cache::put($key, now()->timestamp, now()->addMinutes(self::PARTIDA_TTL_MINUTOS));
            return $this->error('No se pudo guardar el puntaje.', 500);
        }
        $puntajeGuardado = (int) $registro->puntaje;

        return response()->json([
            'success'       => true,
            'message'       => 'Puntaje registrado.',
            'puntaje'       => $puntajeGuardado,
            'mejor_puntaje' => $mejor,
            'es_record'     => $puntajeGuardado >= $mejor,
        ], 201);
    }

    private function partidaKey(int $userId, int $juegoId, string $partidaId): string
    {
        return "partida:{$userId}:{$juegoId}:{$partidaId}";
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
