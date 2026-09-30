<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Ciclo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ciclos';

    protected $fillable = [
        'nombre',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
    public function teams()
    {
        return $this->hasMany(Team::class);
    }
    public function transacciones()
    {
        return $this->hasMany(Transaccion::class);
    }
    public function causaComun()
    {
        return $this->hasMany(CausaComun::class);
    }
    public static function vigente(): ?self
    {
        return static::activo()->latest('id')->first();
    }
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('estado', 'ACTIVO');
    }
}
