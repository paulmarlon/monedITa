<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Team;
use App\Models\MinijuegoPuntaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Obtener el ciclo activo actual
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        if (!$cicloActivo) {
            return view('dashboard.info', ['error' => 'No hay un ciclo activo en este momento.']);
        }

        // 2. Total de tapitas / recaudación en el ciclo activo usando la tabla real 'transaccions'
        $totalTapitas = DB::table('transaccions')
            ->where('ciclo_id', $cicloActivo->id)
            ->where('tipo_operacion', 'FONDO_ECO_SALESIANO')
            ->sum('monto');

        // 3. Equipos del ciclo ordenados por la suma de transacciones de sus usuarios
        $equiposRanking = Team::where('ciclo_id', $cicloActivo->id)
            ->withSum(['users as total_recaudado' => function ($query) use ($cicloActivo) {
                $query->from('users')
                    ->join('transaccions', 'users.id', '=', 'transaccions.receptor_id')
                    ->where('transaccions.ciclo_id', $cicloActivo->id)
                    ->where('transaccions.tipo_operacion', 'FONDO_ECO_SALESIANO');
            }], 'transaccions.monto')
            ->orderByDesc('total_recaudado')
            ->get();

        // 4. Mayores puntajes en minijuegos del ciclo activo (un solo puntaje máximo por usuario)
        $mejoresPuntajesJuegos = MinijuegoPuntaje::join('users', 'minijuegos_puntajes.user_id', '=', 'users.id')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->join('juegos', 'minijuegos_puntajes.juego_id', '=', 'juegos.id')
            ->where('teams.ciclo_id', $cicloActivo->id)
            ->whereIn('minijuegos_puntajes.id', function ($query) use ($cicloActivo) {
                $query->select(DB::raw('MAX(mp.id)'))
                    ->from('minijuegos_puntajes as mp')
                    ->join('users as u', 'mp.user_id', '=', 'u.id')
                    ->join('teams as t', 'u.team_id', '=', 't.id')
                    ->where('t.ciclo_id', $cicloActivo->id)
                    ->groupBy('mp.user_id');
            })
            ->select(
                'minijuegos_puntajes.*',
                'users.alias',
                'users.avatar as user_avatar', // <-- Ajusta 'avatar' si tu columna se llama diferente (ej. foto, imagen, etc.)
                'juegos.titulo as juego_titulo',
                'teams.nombre as team_nombre',
                'teams.logo as team_logo'
            )
            ->orderByDesc('puntaje')
            ->limit(5)
            ->get();
        return view('dashboard.info', compact(
            'cicloActivo',
            'totalTapitas',
            'equiposRanking',
            'mejoresPuntajesJuegos'
        ));
    }
}
