@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Asignar Permisos al Rol: <span class="badge bg-info text-dark">{{ $role->name }}</span></h1>
        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-key-fill me-1"></i> Lista de Permisos Disponibles (Spatie)
            </h3>
        </div>

        <form action="{{ route('roles.permissions', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                @if ($permissions->isEmpty())
                    <div class="alert alert-warning text-center">
                        No hay permisos registrados en el sistema todavía.
                    </div>
                @else
                    <div class="row">
                        @foreach ($permissions as $moduleName => $groupPermissions)
                            <div class="col-md-6 mb-4">
                                {{-- Tarjeta contenedor por cada módulo --}}
                                <div class="card border shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="m-0 text-uppercase fw-bold text-primary">
                                            <i class="bi bi-folder2-open me-1"></i> Módulo: {{ $moduleName }}
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            @foreach ($groupPermissions as $permission)
                                                <div class="col-3 mb-2">
                                                    <div
                                                        class="form-check d-flex align-items-center p-2 rounded hover-bg-light border-bottom m-0">
                                                        <input class="form-check-input shrink-0 mt-0 me-2" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                                            style="cursor: pointer;">

                                                        <label
                                                            class="form-check-label fw-semibold d-flex justify-content-between align-items-center w-100 mb-0"
                                                            for="permission_{{ $permission->id }}" style="cursor: pointer;">
                                                            {{-- Se usa text-body para que cambie de color automáticamente entre modo claro y oscuro --}}
                                                            <span
                                                                class="text-truncate text-body">{{ explode('_', $permission->name)[0] }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card-footer text-end">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-success shadow-sm">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
@stop
