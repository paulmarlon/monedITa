@extends('adminlte::page')

@section('title', 'Configuración del Sistema')

{{-- Activamos SweetAlert2 para las notificaciones Toast --}}
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Configuración <b>General</b></h1>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        <div class="card card-primary card-outline shadow">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-gear-fill me-1"></i> Personalizar Parámetros del Sistema
                </h3>
            </div>

            <form action="{{ route('configuracion.update', $configuracion->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="card-body">

                    <div class="row">
                        {{-- Nombre --}}
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label font-weight-bold">Nombre del Sistema / Institución <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-success"><i class="bi bi-building"></i></span>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                    id="nombre" name="nombre" value="{{ old('nombre', $configuracion->nombre) }}"
                                    placeholder="Ej: Sistema Monedita" required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Correo --}}
                        <div class="col-md-6 mb-3">
                            <label for="correo" class="form-label font-weight-bold">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control @error('correo') is-invalid @enderror"
                                    id="correo" name="correo" value="{{ old('correo', $configuracion->correo) }}"
                                    placeholder="contacto@tudominio.com">
                                @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Teléfono --}}
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label font-weight-bold">Teléfono / Celular</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                    id="telefono" name="telefono" value="{{ old('telefono', $configuracion->telefono) }}"
                                    placeholder="+591 ...">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Sitio Web --}}
                        <div class="col-md-6 mb-3">
                            <label for="web" class="form-label font-weight-bold">Sitio Web Oficial</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control @error('web') is-invalid @enderror" id="web"
                                    name="web" value="{{ old('web', $configuracion->web) }}"
                                    placeholder="https://tuweb.com">
                                @error('web')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Dirección --}}
                        <div class="col-md-12 mb-3">
                            <label for="direccion" class="form-label font-weight-bold">Dirección Física</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror"
                                    id="direccion" name="direccion"
                                    value="{{ old('direccion', $configuracion->direccion) }}"
                                    placeholder="Ubicación o dirección institucional">
                                @error('direccion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Descripción --}}
                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label font-weight-bold">Descripción / Misión
                                General</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion"
                                rows="3" placeholder="Breve reseña o detalles del sistema">{{ old('descripcion', $configuracion->descripcion) }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row align-items-center">
                        {{-- Input para cargar el Logo --}}
                        <div class="col-md-7 mb-3">
                            <label for="logo" class="form-label font-weight-bold">Logotipo del Sistema</label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo"
                                name="logo" accept="image/*">
                            <small class="text-muted d-block mt-1">Formatos recomendados: PNG, JPG, SVG. Máximo 2MB.</small>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Contenedor de Previsualización en vivo --}}
                        <div class="col-md-5 text-center mb-3">
                            <label class="form-label font-weight-bold d-block">Previsualización del Logo</label>
                            <div class="p-2 border rounded bg-dark d-inline-block shadow-sm"
                                style="min-height: 100px; min-width: 120px; display: flex; align-items: center; justify-content: center;">
                                <img id="logoPreview"
                                    src="{{ $configuracion->logo ? asset('storage/' . $configuracion->logo) : '#' }}"
                                    alt="Logo del sistema"
                                    class="img-fluid rounded {{ $configuracion->logo ? '' : 'd-none' }}"
                                    style="max-height: 90px; object-fit: contain;">

                                <span id="noLogoText"
                                    class="text-muted small {{ $configuracion->logo ? 'd-none' : '' }}">
                                    <i class="bi bi-image fs-4 d-block mb-1"></i> Sin imagen seleccionada
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-success shadow-sm px-4">
                        <i class="bi bi-save2 me-1"></i> Guardar Cambios
                    </button>
                </div>

            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Script para previsualizar la imagen antes de subirla
        document.getElementById('logo').addEventListener('change', function(event) {
            const [file] = event.target.files;
            const preview = document.getElementById('logoPreview');
            const noLogoText = document.getElementById('noLogoText');

            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
                noLogoText.classList.add('d-none');
            }
        });

        // Alerta Toast de SweetAlert2 al guardar con éxito
        @if (session('mensaje'))
            Swal.fire({
                position: 'top-end',
                icon: '{{ session('icon', 'success') }}',
                title: '{{ session('mensaje') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                background: '#343a40',
                color: '#ffffff'
            });
        @endif
    </script>
@stop
