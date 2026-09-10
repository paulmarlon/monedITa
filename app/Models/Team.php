<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'ciclo_id',
        'logo',
    ];

    /**
     * Relación: Un Team pertenece a un Ciclo.
     */
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }

    /**
     * Relación: Un Team tiene muchos Usuarios (Estudiantes).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
