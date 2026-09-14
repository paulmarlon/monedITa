@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Editar <b>Equipo</b></h1>
        <a href="{{ route('teams.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-success card-outline shadow">
                <div class="card-header">
                    <h3 class="card-title"><i class="bi bi-pencil-square me-1"></i> Modificar Datos del Equipo</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('teams.update', $team) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="nombre" class="form-label font-weight-bold">Nombre del Equipo</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre"
                                name="nombre" value="{{ old('nombre', $team->nombre) }}" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="ciclo_id" class="form-label font-weight-bold">Ciclo / Periodo Académico</label>
                            <select name="ciclo_id" id="ciclo_id"
                                class="form-control @error('ciclo_id') is-invalid @enderror" required>
                                <option value="">-- Seleccione un Ciclo --</option>
                                @foreach ($ciclos as $ciclo)
                                    <option value="{{ $ciclo->id }}"
                                        {{ old('ciclo_id', $team->ciclo_id) == $ciclo->id ? 'selected' : '' }}>
                                        {{ $ciclo->nombre }} ({{ $ciclo->estado }})
                                    </option>
                                @endforeach
                            </select>
                            @error('ciclo_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Sección de Logotipo con Previsualización --}}
                        <div class="mb-3 text-center">
                            <div class="mb-2">
                                <img id="previewLogo" src="{{ $team->logo ? asset('storage/' . $team->logo) : '#' }}"
                                    alt="Vista previa del logo" class="rounded-circle shadow-sm border"
                                    style="width: 80px; height: 80px; object-fit: cover; {{ $team->logo ? '' : 'display: none;' }}">
                            </div>
                            <small
                                class="text-muted d-block mb-2">{{ $team->logo ? 'Logotipo actual' : 'Sin logotipo' }}</small>

                            <label for="logo" class="form-label font-weight-bold text-start d-block">Cambiar Logotipo
                                (Opcional)</label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo"
                                name="logo" accept="image/*" onchange="previewImage(event)">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success shadow-sm">
                                <i class="bi bi-arrow-repeat me-1"></i> Actualizar Equipo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            const preview = document.getElementById('previewLogo');

            reader.onload = function() {
                if (reader.readyState === 2) {
                    preview.src = reader.result;
                    preview.style.display = 'inline-block';
                }
            }

            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
@stop
