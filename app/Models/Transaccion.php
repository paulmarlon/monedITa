<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    use HasFactory;

    protected $table = 'transaccions';

    // Al ser una tabla de libro mayor, no modificamos datos, solo los creamos
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'ciclo_id',
        'emisor_id',
        'receptor_id',
        'monto',
        'tipo_operacion',
        'observacion',
    ];

    // Relación con el emisor (quien envía)
    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    // Relación con el receptor (quien recibe)
    public function receptor()
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    // Relación con el ciclo
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }
}
