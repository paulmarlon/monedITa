@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><b>Zona Protegida</b></h1>
    </div>
@stop

@section('content')
    <div class="row justify-content-center py-5">
        <div class="col-md-8 text-center">
            <div class="card card-outline card-warning shadow-sm border-0 py-4">
                <div class="card-body">
                    <!-- Contenedor circular adaptativo para modo claro y oscuro -->
                    <div class="mb-4 d-inline-block p-3 rounded-circle border border-success shadow-sm bg-body">
                        <img src="{{ asset('storage/usb/don bosco.png') }}" alt="Logo Institucional"
                            style="height: 100px; width: 100px; object-fit: cover;" class="rounded-circle img-fluid">
                    </div>

                    <h2 class="fw-bold text-body mb-3">¡Vaya, por aquí no es!</h2>

                    <p class="text-muted fs-5 px-md-5 mb-4">
                        Parece que has intentado entrar a un rincón del sistema que requiere permisos especiales dentro de
                        nuestra comunidad. ¡Tranquilo, todo está bajo control!
                    </p>

                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('dashboard.info') }}" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-house-door-fill me-1"></i> Volver al Inicio
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary px-4 shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i> Regresar atrás
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
