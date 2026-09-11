<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Juego extends Model
{
    use SoftDeletes;
    protected $table = 'juegos';

    protected $fillable = [
        'slug',
        'titulo',
        'descripcion',
        'costo_ficha',
        'activo',
    ];

    // Relación: Un juego tiene muchos registros de puntajes/réCORDS
    public function puntajes(): HasMany
    {
        return $this->hasMany(MinijuegoPuntaje::class);
    }
}
