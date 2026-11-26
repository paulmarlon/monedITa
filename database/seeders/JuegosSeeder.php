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
                'slug' => 'taps',
                'titulo' => 'Contador de toques',
                'descripcion' => 'Toca la pantalla lo más rápido posible en 10 segundos.',
                'costo_ficha' => 1,
                'activo' => true,
            ],
            [
                'slug' => 'cube_runner',
                'titulo' => 'Cube Runner F.E.S.',
                'descripcion' => 'El clásico juego del dinosaurio adaptado para la plataforma. Salta los obstáculos y acumula la mayor cantidad de puntos.',
                'costo_ficha' => 1.00,
                'activo' => true,
            ],
            [
                'slug' => 'sapito-avenida',
                'titulo' => 'Sapito sobre avenida',
                'descripcion' => 'Cruza la avenida esquivando el tráfico a alta velocidad y suma puntos en cada carril.',
                'costo_ficha' => 1,
                'activo' => true,
            ],
        ];

        foreach ($juegos as $juego) {
            Juego::updateOrCreate(
                ['slug' => $juego['slug']],
                [
                    'titulo' => $juego['titulo'],
                    'descripcion' => $juego['descripcion'],
                    'costo_ficha' => $juego['costo_ficha'],
                    'activo' => $juego['activo'],
                ]
            );
        }
    }
}
