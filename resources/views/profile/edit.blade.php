@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">

@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="bi bi-person-badge text-primary"></i> Mi Perfil</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Perfil</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')


    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-controller"></i> Configuración de Cuenta y Alianza de Equipo
                        </h3>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="card-body">

                            <!-- BANNER SÓLO IMÁGENES -->
                            <div class="row mb-4">
                                <div class="col-md-1"></div>
                                <div class="col-md-10">
                                    <div
                                        class="d-flex align-items-center justify-content-center px-3 py-2 border rounded bg-body shadow-sm">
                                        <div class="text-center">
                                            <img id="avatar-preview"
                                                src="{{ $user->avatar ? $user->avatar : asset('vendor/adminlte/dist/assets/img/avatar.png') }}"
                                                alt="Usuario" class="rounded-circle border shadow-sm" width="55"
                                                height="55" style="object-fit: cover;">
                                        </div>

                                        <div class="text-center px-4">
                                            <span class="text-muted fs-4">
                                                <i class="bi bi-arrow-right"></i>
                                            </span>
                                        </div>

                                        <div class="text-center">
                                            <img id="teamLogoPreview"
                                                src="{{ $user->team && $user->team->logo ? $user->team->logo : asset('vendor/adminlte/dist/assets/img/AdminLTELogo.png') }}"
                                                alt="Equipo" class="rounded-circle border shadow-sm" width="55"
                                                height="55" style="object-fit: cover;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1"></div>
                            </div>

                            <!-- Fila 1: Información fija (Solo lectura) -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-muted">Nombre Completo</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="alias" class="form-label small fw-bold">Alias en la Plataforma</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input type="text" name="alias" id="alias"
                                            class="form-control @error('alias') is-invalid @enderror"
                                            value="{{ old('alias', $user->alias) }}" required>
                                        @error('alias')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                            </div>

                            <hr class="my-2">

                            <!-- Fila 2: Correo Electrónico y Alias -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-muted">Registro Universitario
                                        (Login)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                        <input type="text" class="form-control"
                                            value="{{ $user->registro_universitario }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label small fw-bold">Correo Electrónico</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" id="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                            </div>

                            <!-- Fila 3: Selección de Equipo y Carga de Avatar (Abajo del Banner) -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="team_id" class="form-label small fw-bold">Selección de Equipo
                                        Eco-Salesiano</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                                        <select name="team_id" id="team_id"
                                            class="form-control @error('team_id') is-invalid @enderror">
                                            <option value="">-- Sin equipo asignado --</option>
                                            @foreach ($teams as $team)
                                                <option value="{{ $team->id }}"
                                                    data-logo="{{ $team->logo ? $team->logo : asset('vendor/adminlte/dist/assets/img/AdminLTELogo.png') }}"
                                                    data-name="{{ $team->nombre }}"
                                                    {{ old('team_id', $user->team_id) == $team->id ? 'selected' : '' }}>
                                                    {{ $team->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('team_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="avatar" class="form-label small fw-bold">Logo o Avatar Personal
                                        (Skin)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="file" name="avatar" id="avatar"
                                            class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                                        @error('avatar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Formatos PNG, JPG
                                        (Máx. 2MB).</small>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer text-end bg-body">
                            <a href="{{ route('home') }}" class="btn btn-secondary btn-sm me-2">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save"></i> Guardar Cambios
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
        document.addEventListener('DOMContentLoaded', function() {
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatar-preview');
            const teamSelect = document.getElementById('team_id');
            const teamLogoPreview = document.getElementById('teamLogoPreview');

            // 1. Previsualización nativa del Avatar del usuario
            if (avatarInput) {
                avatarInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            avatarPreview.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 2. Cambio dinámico del logo del equipo en el banner minimalista
            if (teamSelect) {
                teamSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const logoUrl = selectedOption.getAttribute('data-logo');

                    if (logoUrl && logoUrl !== '') {
                        teamLogoPreview.src = logoUrl;
                    } else {
                        teamLogoPreview.src =
                            "{{ asset('vendor/adminlte/dist/assets/img/AdminLTELogo.png') }}";
                    }
                });
            }

            // 3. Notificación SweetAlert2
            @if (session('success'))
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: "{!! addslashes(session('success')) !!}",
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    toast: true,
                    background: '#343a40',
                    color: '#ffffff'
                });
            @endif
        });
    </script>
@stop
