<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinijuegoPuntaje extends Model
{
    use HasFactory;

    protected $table = 'minijuegos_puntajes';

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'juego_id',
        'puntaje',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function juego()
    {
        return $this->belongsTo(Juego::class);
    }
}
