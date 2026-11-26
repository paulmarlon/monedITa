<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositivoConfiable extends Model
{
    protected $table = 'dispositivo_confiables';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'verified_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at'  => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
