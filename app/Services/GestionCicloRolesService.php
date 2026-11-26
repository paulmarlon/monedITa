<?php

namespace App\Services;

use App\Models\CausaComun;
use App\Models\Ciclo;
use App\Models\CicloUsuarioRole;
use App\Models\Team;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GestionCicloRolesService
{
    public function cicloActivo(): ?Ciclo
    {
        return Ciclo::vigente();
    }
    /**
     * Devuelve los roles que el usuario autenticado ($actor) tiene permitido asignar.
     */
    public function rolesAsignablesPor(User $actor): array
    {
        if ($actor->hasRole(CicloUsuarioRole::ROLE_ADMIN)) {
            return [
                CicloUsuarioRole::ROLE_ADMIN,
                CicloUsuarioRole::ROLE_SUPERVISOR,
                CicloUsuarioRole::ROLE_ESTUDIANTE,
            ];
        }

        if ($actor->hasRole(CicloUsuarioRole::ROLE_SUPERVISOR)) {
            return [
                CicloUsuarioRole::ROLE_SUPERVISOR,
                CicloUsuarioRole::ROLE_ESTUDIANTE,
            ];
        }

        return [];
    }
    /**
     * Determina si $actor puede editar el rol/equipo de $objetivo.
     */
    public function puedeGestionarUsuario(User $actor, User $objetivo): bool
    {
        // El admin raíz es intocable para todos (incluido él mismo para degradarse)
        if ($objetivo->es_admin_raiz) {
            return false;
        }

        if ($actor->hasRole(CicloUsuarioRole::ROLE_ADMIN)) {
            return true;
        }

        if ($actor->hasRole(CicloUsuarioRole::ROLE_SUPERVISOR)) {
            // Un supervisor nunca puede modificar a un administrador
            return !$objetivo->hasRole(CicloUsuarioRole::ROLE_ADMIN);
        }

        return false;
    }
    public function asignarRolEnCiclo(
        User $objetivo,
        string $roleName,
        ?int $teamId,
        ?Ciclo $ciclo = null,
        ?User $actor = null
    ): CicloUsuarioRole {
        if (!in_array($roleName, CicloUsuarioRole::ROLES_VALIDOS, true)) {
            throw new DomainException("Rol no válido: {$roleName}");
        }

        $ciclo = $ciclo ?? $this->cicloActivo();
        if (!$ciclo || $ciclo->estado !== 'ACTIVO') {
            throw new DomainException('No hay un ciclo ACTIVO para asignar roles o equipos.');
        }

        if ($objetivo->es_admin_raiz) {
            throw new DomainException('El administrador raíz inicial no puede ser modificado por ningún usuario.');
        }

        if ($actor) {
            if (!$this->puedeGestionarUsuario($actor, $objetivo)) {
                throw new DomainException('No tienes permisos para modificar a este usuario.');
            }

            $rolesPermitidos = $this->rolesAsignablesPor($actor);
            if (!in_array($roleName, $rolesPermitidos, true)) {
                throw new DomainException("Tu rol no permite asignar el nivel {$roleName}.");
            }

            if ($actor->id === $objetivo->id && $actor->hasRole(CicloUsuarioRole::ROLE_ADMIN) && $roleName !== CicloUsuarioRole::ROLE_ADMIN) {
                throw new DomainException('No puedes quitarte tu propio rol de ADMINISTRADOR.');
            }
        }

        if (!is_null($teamId)) {
            $perteneceAlCiclo = Team::where('id', $teamId)
                ->where('ciclo_id', $ciclo->id)
                ->exists();

            if (!$perteneceAlCiclo) {
                throw new DomainException('El equipo seleccionado no pertenece al ciclo indicado.');
            }
        }

        return DB::transaction(function () use ($objetivo, $roleName, $teamId, $ciclo, $actor) {
            $registro = CicloUsuarioRole::updateOrCreate(
                [
                    'ciclo_id' => $ciclo->id,
                    'user_id'  => $objetivo->id,
                ],
                [
                    'role_name'    => $roleName,
                    'team_id'      => $teamId,
                    'asignado_por' => $actor?->id,
                ]
            );

            $objetivo->update(['team_id' => $teamId]);
            $objetivo->syncRoles([$roleName]);

            return $registro;
        });
    }
    public function cerrarCicloActual(?Ciclo $ciclo = null, ?Carbon $fechaFin = null): Ciclo
    {
        $ciclo = $ciclo ?? $this->cicloActivo();
        if (!$ciclo) {
            throw new DomainException('No existe un ciclo ACTIVO para cerrar.');
        }

        $ciclo->update([
            'estado'    => 'CERRADO',
            'fecha_fin' => $fechaFin ?? now(),
        ]);

        return $ciclo->fresh();
    }
    public function iniciarNuevoPeriodo(
        string $nombre,
        ?Carbon $fechaInicio = null,
        ?Carbon $fechaFin = null,
        ?int $asignadoPorId = null
    ): Ciclo {
        return DB::transaction(function () use ($nombre, $fechaInicio, $fechaFin, $asignadoPorId) {
            Ciclo::where('estado', 'ACTIVO')->update([
                'estado'    => 'CERRADO',
                'fecha_fin' => now(),
            ]);

            $nuevoCiclo = Ciclo::create([
                'nombre'       => $nombre,
                'estado'       => 'ACTIVO',
                'fecha_inicio' => $fechaInicio ?? now(),
                'fecha_fin'    => $fechaFin,
            ]);

            CausaComun::firstOrCreate(
                ['ciclo_id' => $nuevoCiclo->id],
                [
                    'total_acumulado' => 0.00,
                    'descripcion'     => 'Fondo común del ciclo ' . $nuevoCiclo->nombre,
                ]
            );

            User::query()->update(['team_id' => null]);

            User::query()->chunkById(100, function ($usuarios) use ($nuevoCiclo, $asignadoPorId) {
                foreach ($usuarios as $usuario) {
                    $rolInicial = $usuario->es_admin_raiz
                        ? CicloUsuarioRole::ROLE_ADMIN
                        : CicloUsuarioRole::ROLE_ESTUDIANTE;

                    CicloUsuarioRole::updateOrCreate(
                        [
                            'ciclo_id' => $nuevoCiclo->id,
                            'user_id'  => $usuario->id,
                        ],
                        [
                            'role_name'    => $rolInicial,
                            'team_id'      => null,
                            'asignado_por' => $asignadoPorId,
                        ]
                    );

                    $usuario->syncRoles([$rolInicial]);
                }
            });

            return $nuevoCiclo;
        });
    }
    public function enviarCicloAPapelera(Ciclo $ciclo): Ciclo
    {
        if ($ciclo->estado === 'ACTIVO') {
            throw new DomainException('No puedes enviar a papelera un ciclo ACTIVO. Ciérralo o inicia uno nuevo primero.');
        }

        $ciclo->delete();

        return $ciclo;
    }

    public function restaurarCicloDesdePapelera(int $cicloId): Ciclo
    {
        $ciclo = Ciclo::onlyTrashed()->findOrFail($cicloId);
        $ciclo->restore();

        return $ciclo->fresh();
    }
}
