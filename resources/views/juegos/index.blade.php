@extends('adminlte::page')

@section('title', 'Catálogo de Minijuegos')

{{-- Activamos los plugins de DataTables y SweetAlert2 --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de <b>Minijuegos</b></h1>
        <div>
            <!-- Botón de Papelera añadido -->
            <a href="{{ route('juegos.trash') }}" class="btn btn-outline-secondary me-2 btn-sm shadow-sm">
                <i class="bi bi-trash3 me-1"></i> Papelera
            </a>
            <a href="{{ route('juegos.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Juego
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Tabla Principal -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline shadow">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-controller me-1"></i> Listado de juegos disponibles y costos de fichas
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="juegosTable"
                                class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-2" style="width: 10px">#</th>
                                        <th class="py-2">Slug (Identificador)</th>
                                        <th class="py-2">Título</th>
                                        <th class="py-2">Costo Ficha</th>
                                        <th class="py-2">Estado</th>
                                        <th class="text-center py-2" style="width: 160px">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($juegos as $index => $juego)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $juego->slug }}</code></td>
                                            <td class="fw-semibold">{{ $juego->titulo }}</td>
                                            <td>{{ number_format($juego->costo_ficha, 2) }} 🪙</td>
                                            <td>
                                                @if ($juego->activo)
                                                    <span class="badge bg-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                    <!-- Botón Ver -->
                                                    <a href="{{ route('juegos.show', $juego) }}" class="btn btn-info btn-sm"
                                                        title="Ver detalles">
                                                        <i class="bi bi-eye text-white"></i>
                                                    </a>
                                                    <!-- Botón Editar -->
                                                    <a href="{{ route('juegos.edit', $juego) }}"
                                                        class="btn btn-success btn-sm" title="Editar">
                                                        <i class="bi bi-pencil-square text-white"></i>
                                                    </a>
                                                    <!-- Botón Eliminar con SweetAlert -->
                                                    <form action="{{ route('juegos.destroy', $juego) }}" method="POST"
                                                        class="d-inline" id="formEliminar{{ $juego->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm rounded-0 rounded-end"
                                                            title="Eliminar"
                                                            onclick="confirmarEliminacion({{ $juego->id }})">
                                                            <i class="bi bi-trash text-white"></i>
                                                        </button>
                                                    </form>
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
            if ($('#juegosTable').length) {
                $('#juegosTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay minijuegos registrados todavía en el sistema",
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

        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "El juego será enviado a la papelera.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: '#343a40',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formEliminar' + id).submit();
                }
            });
        }
    </script>
@endsection
