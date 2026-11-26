<?php

namespace Database\Seeders;

use App\Models\Juego;
use Illuminate\Database\Seeder;

class JuegosSeeder extends Seeder
{
    /**
     * Los slugs deben ser IDÉNTICOS a los de la app Android:
     *   TapCounterGame -> "taps" | CubeRunnerGame -> "cube_runner" | SapitoGame -> "sapito-avenida"
     */
    public function run(): void
    {
        $juegos = [
            [
                'slug' => 'taps',
                'titulo' => 'Contador de toques',
                'descripcion' => 'Toca la pantalla lo más rápido posible en 10 segundos.',
            ],
            [
                'slug' => 'cube_runner',
                'titulo' => 'Cube Runner Eco.',
                'descripcion' => 'El clásico juego del dinosaurio adaptado para la plataforma. Salta los obstáculos y acumula la mayor cantidad de puntos.',
            ],
            [
                'slug' => 'sapito-avenida',
                'titulo' => 'Sapito sobre avenida',
                'descripcion' => 'Cruza la avenida esquivando el tráfico a alta velocidad y suma puntos en cada carril.',
            ],
        ];

        foreach ($juegos as $j) {
            // withTrashed: si el juego fue eliminado (SoftDeletes) se restaura en vez de chocar con el slug único
            $juego = Juego::withTrashed()->firstOrNew(['slug' => $j['slug']]);
            $juego->fill($j + ['costo_ficha' => 1.00, 'activo' => true]);
            $juego->deleted_at = null;
            $juego->save();
        }
    }
}
