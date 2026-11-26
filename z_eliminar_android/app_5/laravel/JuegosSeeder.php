<?php

namespace Database\Seeders;

use App\Models\Juego;
use Illuminate\Database\Seeder;

class JuegosSeeder extends Seeder
{
    public function run(): void
    {
        $juegos = [
            [
                'slug'        => 'taps', // el slug debe coincidir EXACTO con el de la app
                'titulo'      => 'Contador de toques',
                'descripcion' => 'Toca la pantalla lo más rápido posible en 10 segundos.',
            ],
            [
                'slug'        => 'cube_runner',
                'titulo'      => 'Cube Runner Eco.',
                'descripcion' => 'El clásico juego del dinosaurio adaptado para la plataforma. Salta los obstáculos y acumula la mayor cantidad de puntos.',
            ],
            [
                'slug'        => 'sapito',
                'titulo'      => 'Sapito sobre avenida',
                'descripcion' => 'Cruza la avenida esquivando los vehículos. Cada carril cuenta y cada ola se pone más rápida.',
            ],
        ];

        foreach ($juegos as $j) {
            Juego::updateOrCreate(
                ['slug' => $j['slug']],
                $j + ['costo_ficha' => 1.00, 'activo' => true]
            );
        }
    }
}
