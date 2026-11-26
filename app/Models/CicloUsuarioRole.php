<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CicloUsuarioRole extends Model
{
    public const ROLE_ADMIN = 'ADMINISTRADOR';
    public const ROLE_SUPERVISOR = 'SUPERVISOR';
    public const ROLE_ESTUDIANTE = 'ESTUDIANTE';

    public const ROLES_VALIDOS = [
        self::ROLE_ADMIN,
        self::ROLE_SUPERVISOR,
        self::ROLE_ESTUDIANTE,
    ];

    protected $table = 'ciclo_usuario_roles';

    protected $fillable = [
        'ciclo_id',
        'user_id',
        'role_name',
        'team_id',
        'asignado_por',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }
}
