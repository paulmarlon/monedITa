<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CausaComun extends Model
{
    use HasFactory;

    protected $table = 'causa_comun';

    protected $fillable = [
        'ciclo_id',
        'total_acumulado',
        'descripcion'
    ];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }
}
