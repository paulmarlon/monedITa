@extends('adminlte::page')

@section('title', 'Control de Usuarios y Ciclos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="m-0">Control de Usuarios y Ciclos Académicos</h1>
            <small class="text-muted">Asignación de roles por período, cierre de campaña y nueva partida</small>
        </div>
        <div>
            @if ($cicloActivo)
                <span class="badge bg-success fs-6">
                    <i class="bi bi-check-circle me-1"></i>
                    Ciclo ACTIVO: {{ $cicloActivo->nombre }}
                </span>
            @else
                <span class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Sin ciclo ACTIVO
                </span>
            @endif
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- Gestión de Ciclo / Nueva Partida --}}
    @role('ADMINISTRADOR')
        <div class="card card-outline card-dark mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-archive me-1"></i> Historial y Papelera de Ciclos</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Período</th>
                            <th>Estado</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ciclos as $c)
                            <tr>
                                <td><strong>{{ $c->nombre }}</strong></td>
                                <td>
                                    <span class="badge {{ $c->estado === 'ACTIVO' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $c->estado }}
                                    </span>
                                </td>
                                <td class="small">{{ optional($c->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                <td class="small">{{ optional($c->fecha_fin)->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-end">
                                    @if ($c->estado !== 'ACTIVO')
                                        <form action="{{ route('admin.usuarios-ciclos.ciclos.papelera', $c) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('¿Enviar el ciclo \"{{ $c->nombre }}\" a la papelera?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-xs">
                                                <i class="bi bi-trash3"></i> Papelera
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">En uso</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @foreach ($ciclosEnPapelera as $eliminado)
                            <tr class="table-warning">
                                <td><span class="text-decoration-line-through">{{ $eliminado->nombre }}</span></td>
                                <td><span class="badge bg-danger">EN PAPELERA</span></td>
                                <td class="small">{{ optional($eliminado->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                <td class="small">{{ optional($eliminado->deleted_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.usuarios-ciclos.ciclos.restaurar', $eliminado->id) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-xs">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endrole

    {{-- Filtro por ciclo histórico y búsqueda de usuarios --}}
    <div class="card card-outline card-secondary">
        <div class="card-header">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <h3 class="card-title"><i class="bi bi-people me-1"></i> Usuarios y Asignación de Roles</h3>
                </div>
                <div class="col-md-8">
                    <form method="GET" action="{{ route('admin.usuarios-ciclos.index') }}"
                        class="row g-2 justify-content-end">
                        <div class="col-sm-4">
                            <select name="ciclo_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach ($ciclos as $c)
                                    <option value="{{ $c->id }}" @selected($cicloConsultado && $cicloConsultado->id === $c->id)>
                                        {{ $c->nombre }} ({{ $c->estado }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <input type="text" name="buscar" value="{{ $buscar }}"
                                class="form-control form-control-sm" placeholder="Buscar por RU, nombre, alias o correo...">
                        </div>
                        <div class="col-sm-3 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>RU</th>
                        <th>Usuario / Alias</th>
                        <th>Correo</th>
                        <th>Rol Actual (Spatie)</th>
                        <th>Registro en {{ $cicloConsultado->nombre ?? 'Ciclo' }}</th>
                        <th style="min-width: 310px;">Asignar Rol y Equipo
                            ({{ $cicloActivo->nombre ?? 'Sin ciclo activo' }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        @php
                            $rolActualSpatie = $usuario->roles->pluck('name')->first() ?? 'ESTUDIANTE';
                            $asignacion = $asignacionesCiclo->get($usuario->id);
                        @endphp
                        <tr>
                            <td><code>{{ $usuario->registro_universitario }}</code></td>
                            <td>
                                <strong>{{ $usuario->name }}</strong>
                                @if ($usuario->es_admin_raiz)
                                    <span class="badge bg-dark ms-1" title="Administrador raíz protegido">RAÍZ</span>
                                @endif
                                <div class="text-muted small">{{ '@' . $usuario->alias }}</div>
                            </td>
                            <td class="small">{{ $usuario->email }}</td>
                            <td>
                                <span
                                    class="badge @if ($rolActualSpatie === 'ADMINISTRADOR') bg-danger @elseif($rolActualSpatie === 'SUPERVISOR') bg-info text-dark @else bg-secondary @endif">
                                    {{ $rolActualSpatie }}
                                </span>
                            </td>
                            <td class="small">
                                @if ($asignacion)
                                    <div><strong>Rol:</strong> {{ $asignacion->role_name }}</div>
                                    <div class="text-muted"><strong>Team:</strong>
                                        {{ $asignacion->team->nombre ?? 'Sin equipo' }}</div>
                                @else
                                    <span class="text-muted">Sin registro en este ciclo</span>
                                @endif
                            </td>
                            <td>
                                @if ($usuario->es_admin_raiz)
                                    <span class="badge bg-dark">
                                        <i class="bi bi-shield-lock-fill me-1"></i> Admin raíz protegido
                                    </span>
                                @elseif (!$cicloActivo || $cicloActivo->estado !== 'ACTIVO')
                                    <span class="text-muted small">Activa un ciclo para asignar</span>
                                @elseif (!$gestionService->puedeGestionarUsuario(auth()->user(), $usuario))
                                    <span class="text-muted small">Rol superior protegido</span>
                                @else
                                    <form action="{{ route('admin.usuarios-ciclos.asignar', $usuario) }}" method="POST"
                                        class="d-flex gap-1 align-items-center">
                                        @csrf
                                        <select name="role_name" class="form-select form-select-sm" style="width: 145px;">
                                            @foreach ($rolesAsignables as $rol)
                                                <option value="{{ $rol }}" @selected(($asignacion && $cicloActivo->id === $cicloConsultado?->id ? $asignacion->role_name : $rolActualSpatie) === $rol)>
                                                    {{ $rol }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="team_id" class="form-select form-select-sm" style="width: 145px;">
                                            <option value="">— Sin equipo —</option>
                                            @foreach ($teamsCicloActivo as $team)
                                                <option value="{{ $team->id }}" @selected((int) $usuario->team_id === (int) $team->id)>
                                                    {{ $team->nombre }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-sm btn-primary" title="Guardar asignación">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="card-footer clearfix">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
@stop
