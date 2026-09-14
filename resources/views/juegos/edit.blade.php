@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de <b>Minijuegos</b></h1>
        <div>
            <a href="{{ route('juegos.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card card-primary card-outline shadow">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-pencil-square me-1"></i> Editar Minijuego: <b>{{ $juego->titulo }}</b>
                        </h3>
                    </div>

                    <!-- Formulario de Edición -->
                    <form action="{{ route('juegos.update', $juego) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <!-- Mensaje de errores globales de validación -->
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="row">
                                <!-- Columna Izquierda -->
                                <div class="col-md-6">
                                    <!-- Título del Juego -->
                                    <div class="form-group mb-3">
                                        <label for="titulo" class="form-label">
                                            <i class="bi bi-controller text-primary me-1"></i> Título del Juego <span
                                                class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="titulo" id="titulo"
                                            class="form-control @error('titulo') is-invalid @enderror"
                                            value="{{ old('titulo', $juego->titulo) }}" placeholder="Ej: Dinosaurio Runner"
                                            required>
                                        @error('titulo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Costo de Ficha -->
                                    <div class="form-group mb-3">
                                        <label for="costo_ficha" class="form-label">
                                            <i class="bi bi-coin text-warning me-1"></i> Costo de Ficha <span
                                                class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" name="costo_ficha"
                                                id="costo_ficha"
                                                class="form-control @error('costo_ficha') is-invalid @enderror"
                                                value="{{ old('costo_ficha', $juego->costo_ficha) }}" required>
                                            <span class="input-group-text">🪙</span>
                                            @error('costo_ficha')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">Moneditas descontadas al iniciar.</small>
                                    </div>
                                </div>

                                <!-- Columna Derecha -->
                                <div class="col-md-6">
                                    <!-- Slug / Identificador único -->
                                    <div class="form-group mb-3">
                                        <label for="slug" class="form-label">
                                            <i class="bi bi-code-slash text-info me-1"></i> Slug (Identificador) <span
                                                class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="slug" id="slug"
                                            class="form-control @error('slug') is-invalid @enderror"
                                            value="{{ old('slug', $juego->slug) }}" placeholder="Ej: dinosaurio_runner"
                                            required>
                                        <small class="form-text text-muted">Único, sin espacios (usa guiones bajos
                                            `_`).</small>
                                        @error('slug')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Estado Activo / Inactivo -->
                                    <div class="form-group mb-3">
                                        <label class="form-label d-block">
                                            <i class="bi bi-toggle-on text-success me-1"></i> Estado del Juego
                                        </label>
                                        <div class="custom-control custom-switch mt-2">
                                            <input type="checkbox" class="custom-control-input" id="activo"
                                                name="activo" value="1"
                                                {{ old('activo', $juego->activo) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="activo">Juego Activo (Disponible para
                                                estudiantes)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fila Inferior: Descripción (Ancho completo) -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-2">
                                        <label for="descripcion" class="form-label">
                                            <i class="bi bi-card-text text-secondary me-1"></i> Descripción
                                        </label>
                                        <textarea name="descripcion" id="descripcion" rows="2"
                                            class="form-control @error('descripcion') is-invalid @enderror"
                                            placeholder="Breve descripción de las reglas o dinámica del minijuego...">{{ old('descripcion', $juego->descripcion) }}</textarea>
                                        @error('descripcion')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Botones de Acción -->
                        <div class="card-footer text-right">
                            <a href="{{ route('juegos.index') }}" class="btn btn-secondary shadow-sm">Cancelar</a>
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Juego
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
