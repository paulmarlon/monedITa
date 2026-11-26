<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Juego;
use App\Models\MinijuegoPuntaje;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        if (!$cicloActivo) {
            return view('dashboard.info', ['error' => 'No hay un ciclo activo en este momento.']);
        }

        // Total de tapitas / recaudación en el ciclo activo
        $totalTapitas = DB::table('transaccions')
            ->where('ciclo_id', $cicloActivo->id)
            ->where('tipo_operacion', 'FONDO_ECO_SALESIANO')
            ->sum('monto');

        // Equipos del ciclo ordenados por recaudación
        $equiposRanking = Team::where('ciclo_id', $cicloActivo->id)
            ->withSum(['users as total_recaudado' => function ($query) use ($cicloActivo) {
                $query->from('users')
                    ->join('transaccions', 'users.id', '=', 'transaccions.receptor_id')
                    ->where('transaccions.ciclo_id', $cicloActivo->id)
                    ->where('transaccions.tipo_operacion', 'FONDO_ECO_SALESIANO');
            }], 'transaccions.monto')
            ->orderByDesc('total_recaudado')
            ->get();

        // Juegos registrados en BD
        $juegos = Juego::orderBy('id')->get();

        // Helper para resolver URL de imagen (config en vez de env() en Blade)
        $resolveMediaUrl = function (?string $path): ?string {
            if (!$path) {
                return null;
            }
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            $endpoint = config('filesystems.disks.s3.endpoint');
            $bucket = config('filesystems.disks.s3.bucket');
            if ($endpoint && $bucket) {
                return rtrim($endpoint, '/s3') . '/object/public/' . $bucket . '/' . ltrim($path, '/');
            }
            return asset('storage/' . ltrim($path, '/'));
        };

        // Top 5 por cada juego dentro del ciclo activo (máximo puntaje por usuario)
        $rankingsPorJuego = [];
        $todosLosPuntajesCiclo = collect();

        foreach ($juegos as $juego) {
            $maxScoresSub = DB::table('minijuegos_puntajes as mp')
                ->join('users as u', 'mp.user_id', '=', 'u.id')
                ->join('teams as t', 'u.team_id', '=', 't.id')
                ->where('t.ciclo_id', $cicloActivo->id)
                ->where('mp.juego_id', $juego->id)
                ->select('mp.user_id', DB::raw('MAX(mp.puntaje) as max_puntaje'))
                ->groupBy('mp.user_id');

            $topJuego = MinijuegoPuntaje::joinSub($maxScoresSub, 'best', function ($join) {
                $join->on('minijuegos_puntajes.user_id', '=', 'best.user_id')
                    ->on('minijuegos_puntajes.puntaje', '=', 'best.max_puntaje');
            })
                ->join('users', 'minijuegos_puntajes.user_id', '=', 'users.id')
                ->join('teams', 'users.team_id', '=', 'teams.id')
                ->join('juegos', 'minijuegos_puntajes.juego_id', '=', 'juegos.id')
                ->where('teams.ciclo_id', $cicloActivo->id)
                ->where('minijuegos_puntajes.juego_id', $juego->id)
                ->select(
                    'minijuegos_puntajes.id',
                    'minijuegos_puntajes.user_id',
                    'minijuegos_puntajes.juego_id',
                    'minijuegos_puntajes.puntaje',
                    'users.alias',
                    'users.avatar as user_avatar',
                    'juegos.titulo as juego_titulo',
                    'teams.nombre as team_nombre',
                    'teams.logo as team_logo'
                )
                ->orderByDesc('minijuegos_puntajes.puntaje')
                ->orderBy('minijuegos_puntajes.id')
                ->limit(5)
                ->get()
                ->unique('user_id')
                ->values()
                ->map(function ($row) use ($resolveMediaUrl) {
                    $row->user_avatar_url = $resolveMediaUrl($row->user_avatar ?? null);
                    $row->team_logo_url = $resolveMediaUrl($row->team_logo ?? null);
                    return $row;
                });

            $rankingsPorJuego[$juego->id] = [
                'juego' => $juego,
                'puntajes' => $topJuego,
            ];

            $todosLosPuntajesCiclo = $todosLosPuntajesCiclo->concat($topJuego);
        }

        // Top 5 general entre los mejores de cada juego en el ciclo
        $mejoresPuntajesJuegos = $todosLosPuntajesCiclo
            ->sortByDesc('puntaje')
            ->take(5)
            ->values();

        $equiposRanking->transform(function ($team) use ($resolveMediaUrl) {
            $team->logo_url = $resolveMediaUrl($team->logo ?? null);
            return $team;
        });

        return view('dashboard.info', compact(
            'cicloActivo',
            'totalTapitas',
            'equiposRanking',
            'juegos',
            'rankingsPorJuego',
            'mejoresPuntajesJuegos'
        ));
    }
}
