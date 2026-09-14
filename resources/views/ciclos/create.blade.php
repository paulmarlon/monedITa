@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de <b>Periodos</b></h1>
        <div>
            <a href="{{ route('ciclos.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-plus-circle me-1"></i> Registrar Nuevo Periodo
            </h3>
        </div>

        <form action="{{ route('ciclos.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row">
                    {{-- Nombre del Periodo --}}
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="nombre" class="font-weight-semibold">Nombre del Periodo <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre"
                                class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}"
                                placeholder="Ej: Periodo 1/2026" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Estado del Periodo --}}
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="estado" class="font-weight-semibold">Estado</label>
                            <select name="estado" id="estado"
                                class="form-control @error('estado') is-invalid @enderror">
                                <option value="ACTIVO" {{ old('estado', 'ACTIVO') == 'ACTIVO' ? 'selected' : '' }}>Activo
                                </option>
                                <option value="CERRADO" {{ old('estado') == 'CERRADO' ? 'selected' : '' }}>Cerrado</option>
                            </select>
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Fecha de Inicio --}}
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="fecha_inicio" class="font-weight-semibold">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio"
                                class="form-control @error('fecha_inicio') is-invalid @enderror"
                                value="{{ old('fecha_inicio') }}">
                            @error('fecha_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Fecha de Fin --}}
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="fecha_fin" class="font-weight-semibold">Fecha de Fin</label>
                            <input type="date" name="fecha_fin" id="fecha_fin"
                                class="form-control @error('fecha_fin') is-invalid @enderror"
                                value="{{ old('fecha_fin') }}">
                            @error('fecha_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('ciclos.index') }}" class="btn btn-secondary btn-sm shadow-sm mr-2">
                    <i class="bi bi-x-lg me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-check-lg me-1"></i> Guardar Periodo
                </button>
            </div>
        </form>
    </div>
@stop
