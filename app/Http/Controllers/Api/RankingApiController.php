<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Juego;
use App\Models\MinijuegoPuntaje;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingApiController extends Controller
{
    private const LIMITE = 10;

    /**
     * GET /api/juegos/{slug}/ranking
     * Mejor puntaje de cada usuario, top 10, mas la posicion de quien consulta.
     * Solo expone alias y avatar (no nombre ni registro universitario).
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $juego = Juego::where('slug', $slug)->where('activo', true)->first();

        if (! $juego) {
            return response()->json(['success' => false, 'message' => 'Juego no encontrado.'], 404);
        }

        $userId = (int) $request->user()->id;

        $filas = MinijuegoPuntaje::query()
            ->select('user_id', DB::raw('MAX(puntaje) as mejor'))
            ->where('juego_id', $juego->id)
            ->groupBy('user_id')
            ->orderByDesc('mejor')
            ->orderBy('user_id')
            ->limit(self::LIMITE)
            ->get();

        $usuarios = User::whereIn('id', $filas->pluck('user_id'))
            ->get(['id', 'alias', 'avatar'])
            ->keyBy('id');

        // Posicion de competicion: los empates comparten lugar
        $top = [];
        $posicion = 0;
        $anterior = null;
        foreach ($filas as $i => $fila) {
            $mejor = (int) $fila->mejor;
            if ($mejor !== $anterior) {
                $posicion = $i + 1;
                $anterior = $mejor;
            }
            $u = $usuarios->get($fila->user_id);
            $top[] = [
                'posicion' => $posicion,
                'alias'    => $u?->alias ?? '(sin alias)',
                'avatar'   => $u?->avatar,
                'puntaje'  => $mejor,
                'soy_yo'   => (int) $fila->user_id === $userId,
            ];
        }

        $miMejor = MinijuegoPuntaje::where('user_id', $userId)
            ->where('juego_id', $juego->id)
            ->max('puntaje');

        $miPosicion = null;
        if ($miMejor !== null) {
            $miPosicion = 1 + DB::table('minijuegos_puntajes')
                ->where('juego_id', $juego->id)
                ->groupBy('user_id')
                ->havingRaw('MAX(puntaje) > ?', [(int) $miMejor])
                ->select('user_id')
                ->get()
                ->count();
        }

        return response()->json([
            'success'      => true,
            'juego'        => $juego->titulo,
            'top'          => $top,
            'mi_posicion'  => $miPosicion,
            'mi_mejor'     => $miMejor !== null ? (int) $miMejor : null,
        ]);
    }
}
