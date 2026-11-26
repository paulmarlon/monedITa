<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use App\Models\CicloUsuarioRole;
use App\Models\Team;
use App\Models\User;
use App\Services\GestionCicloRolesService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioCicloController extends Controller
{
    public function __construct(
        private readonly GestionCicloRolesService $gestionService
    ) {}

    public function index(Request $request): View
    {
        $actor = $request->user();
        $cicloActivo = Ciclo::vigente();
        $ciclos = Ciclo::orderByDesc('id')->get();
        $ciclosEnPapelera = Ciclo::onlyTrashed()->orderByDesc('deleted_at')->get();

        $cicloConsultaId = $request->integer('ciclo_id') ?: ($cicloActivo?->id);
        $cicloConsultado = $ciclos->firstWhere('id', $cicloConsultaId) ?? $cicloActivo;

        $teamsCicloActivo = $cicloActivo
            ? Team::where('ciclo_id', $cicloActivo->id)->orderBy('nombre')->get()
            : collect();

        $usuarios = User::with(['roles', 'team'])
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $termino = trim((string) $request->input('buscar'));
                $query->where(function ($sub) use ($termino) {
                    $sub->where('registro_universitario', 'ILIKE', "%{$termino}%")
                        ->orWhere('name', 'ILIKE', "%{$termino}%")
                        ->orWhere('alias', 'ILIKE', "%{$termino}%")
                        ->orWhere('email', 'ILIKE', "%{$termino}%");
                });
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $asignacionesCiclo = $cicloConsultado
            ? CicloUsuarioRole::with(['team', 'asignadoPor'])
            ->where('ciclo_id', $cicloConsultado->id)
            ->get()
            ->keyBy('user_id')
            : collect();

        return view('admin.usuarios-ciclos.index', [
            'cicloActivo'       => $cicloActivo,
            'ciclos'            => $ciclos,
            'ciclosEnPapelera'  => $ciclosEnPapelera,
            'cicloConsultado'   => $cicloConsultado,
            'teamsCicloActivo'  => $teamsCicloActivo,
            'usuarios'          => $usuarios,
            'asignacionesCiclo' => $asignacionesCiclo,
            'rolesAsignables'   => $this->gestionService->rolesAsignablesPor($actor),
            'gestionService'    => $this->gestionService,
            'buscar'            => $request->input('buscar', ''),
            'rolesValidos' => CicloUsuarioRole::ROLES_VALIDOS,
        ]);
    }

    public function asignarRol(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $cicloActivo = Ciclo::vigente();
        $teamIdsValidos = $cicloActivo
            ? Team::where('ciclo_id', $cicloActivo->id)->pluck('id')->all()
            : [];

        $validated = $request->validate([
            'role_name' => ['required', 'string', Rule::in($this->gestionService->rolesAsignablesPor($actor))],
            'team_id'   => ['nullable', 'integer', Rule::in($teamIdsValidos)],
        ]);

        try {
            $this->gestionService->asignarRolEnCiclo(
                objetivo: $user,
                roleName: $validated['role_name'],
                teamId: $validated['team_id'] ?? null,
                ciclo: $cicloActivo,
                actor: $actor
            );

            return back()->with('success', "Rol y equipo actualizados para {$user->alias} ({$user->registro_universitario}).");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cerrarCiclo(): RedirectResponse
    {
        try {
            $cicloCerrado = $this->gestionService->cerrarCicloActual();
            return back()->with('success', "El ciclo \"{$cicloCerrado->nombre}\" se marcó como CERRADO.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function nuevoPeriodo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'       => ['required', 'string', 'max:120'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        try {
            $nuevo = $this->gestionService->iniciarNuevoPeriodo(
                nombre: $validated['nombre'],
                fechaInicio: !empty($validated['fecha_inicio']) ? now()->parse($validated['fecha_inicio']) : now(),
                fechaFin: !empty($validated['fecha_fin']) ? now()->parse($validated['fecha_fin']) : null,
                asignadoPorId: $request->user()?->id
            );

            return redirect()
                ->route('admin.usuarios-ciclos.index', ['ciclo_id' => $nuevo->id])
                ->with('success', "Nuevo período \"{$nuevo->nombre}\" activado. Roles reiniciados para nueva partida (admin raíz conservado).");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function enviarPapelera(Ciclo $ciclo): RedirectResponse
    {
        try {
            $this->gestionService->enviarCicloAPapelera($ciclo);
            return back()->with('success', "El ciclo \"{$ciclo->nombre}\" se envió a la papelera.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function restaurar(int $id): RedirectResponse
    {
        try {
            $ciclo = $this->gestionService->restaurarCicloDesdePapelera($id);
            return back()->with('success', "El ciclo \"{$ciclo->nombre}\" fue restaurado desde la papelera.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
