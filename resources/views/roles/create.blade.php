@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="bi bi-shield-plus me-2"></i> Crear Nuevo Rol</h1>
            </div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        {{-- Mostrar errores de validación --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>¡Atención!</strong> Revisa los campos
                obligatorios.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Formulario de Registro de Rol</h3>
            </div>

            <form action="{{ route('roles.store') }}" method="POST">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label font-weight-bold">Nombre del Rol</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}"
                            placeholder="Ej: Administrador, Estudiante, Coordinador..." required>

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Guardar Rol
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop
