@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

{{-- Activamos los plugins de DataTables y SweetAlert2 --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Papelera de <b>Minijuegos</b></h1>
        <div>
            <!-- Botón para volver al listado principal -->
            <a href="{{ route('juegos.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Catálogo
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Tabla de la Papelera -->
        <div class="row">
            <div class="col-12">
                <div class="card card-warning card-outline shadow">
                    <div class="card-header">
                        <h3 class="card-title text-warning">
                            <i class="bi bi-trash3 me-1"></i> Minijuegos eliminados (Disponibles para restaurar)
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="trashTable"
                                class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-2" style="width: 10px">#</th>
                                        <th class="py-2">Slug (Identificador)</th>
                                        <th class="py-2">Título</th>
                                        <th class="py-2">Fecha de Eliminación</th>
                                        <th class="text-center py-2" style="width: 160px">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($juegos as $index => $juego)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $juego->slug }}</code></td>
                                            <td class="fw-semibold">{{ $juego->titulo }}</td>
                                            <td>{{ $juego->deleted_at ? $juego->deleted_at->format('d/m/Y H:i') : '-' }}
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                    <!-- Botón Restaurar -->
                                                    <form action="{{ route('juegos.restore', $juego->id) }}" method="POST"
                                                        class="d-inline" id="formRestaurar{{ $juego->id }}">
                                                        @csrf
                                                        <button type="button" class="btn btn-success btn-sm"
                                                            title="Restaurar juego"
                                                            onclick="confirmarRestauracion({{ $juego->id }})">
                                                            <i class="bi bi-arrow-counterclockwise text-white"></i>
                                                            Restaurar
                                                        </button>
                                                    </form>

                                                    <!-- Botón Eliminar Permanentemente (Opcional) -->
                                                    @isset($juego->id)
                                                        <form action="{{ route('juegos.forceDelete', $juego->id) }}"
                                                            method="POST" class="d-inline ms-1"
                                                            id="formForzarEliminacion{{ $juego->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                title="Eliminar permanentemente"
                                                                onclick="confirmarEliminacionDefinitiva({{ $juego->id }})">
                                                                <i class="bi bi-x-lg text-white"></i>
                                                            </button>
                                                        </form>
                                                    @endisset
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <!-- El vacío lo maneja DataTables -->
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($('#trashTable').length) {
                $('#trashTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "La papelera está vacía",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "infoEmpty": "Mostrando 0 to 0 of 0 registros",
                        "infoFiltered": "(filtrado de _MAX_ registros totales)",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "No se encontraron registros coincidentes",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        },
                        "buttons": {
                            "copy": "Copiar",
                            "excel": "Excel",
                            "pdf": "PDF",
                            "print": "Imprimir",
                            "colvis": "Columnas"
                        }
                    },
                    dom: '<"row mx-0 border-bottom py-2"<"col-sm-5"B><"col-sm-3"l><"col-sm-4"f>>rt<"row mx-0 pt-2"<"col-sm-6"i><"col-sm-6"p>>',
                    buttons: [{
                            extend: 'copy',
                            className: 'btn btn-secondary btn-sm shadow-sm'
                        },
                        {
                            extend: 'excel',
                            className: 'btn btn-success btn-sm shadow-sm'
                        },
                        {
                            extend: 'pdf',
                            className: 'btn btn-danger btn-sm shadow-sm'
                        },
                        {
                            extend: 'print',
                            className: 'btn btn-info btn-sm shadow-sm'
                        },
                        {
                            extend: 'colvis',
                            className: 'btn btn-dark btn-sm shadow-sm'
                        }
                    ]
                });
            }

            @if (session('success'))
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    toast: true,
                    background: '#343a40',
                    color: '#ffffff'
                });
            @endif
        });

        function confirmarRestauracion(id) {
            Swal.fire({
                title: '¿Restaurar minijuego?',
                text: "El juego volverá a estar disponible en el catálogo activo.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar',
                background: '#343a40',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formRestaurar' + id).submit();
                }
            });
        }

        function confirmarEliminacionDefinitiva(id) {
            Swal.fire({
                title: '¿Eliminar permanentemente?',
                text: "Esta acción no se puede deshacer y borrará los datos del registro.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, borrar para siempre',
                cancelButtonText: 'Cancelar',
                background: '#343a40',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formForzarEliminacion' + id).submit();
                }
            });
        }
    </script>
@endsection
