@extends('adminlte::page')
@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop
{{-- Activamos los plugins configurados globalmente --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de <b>Periodos</b></h1>
        <div>
            <a href="{{ route('ciclos.trash') }}" class="btn btn-outline-secondary me-2 btn-sm shadow-sm">
                <i class="bi bi-trash3 me-1"></i> Papelera
            </a>
            <a href="{{ route('ciclos.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Crear Periodo
            </a>
        </div>
    </div>
@stop
@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-calendar-event me-1"></i> Listado General de Periodos Activos
            </h3>
        </div>
        <div class="card-body">
            <table id="ciclosTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2">#</th>
                        <th class="py-2">Nombre</th>
                        <th class="py-2">Estado</th>
                        <th class="py-2">Fecha Inicio</th>
                        <th class="py-2">Fecha Fin</th>
                        <th style="width: 120px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ciclos as $index => $ciclo)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $ciclo->nombre }}</td>
                            <td>
                                @if (strtoupper($ciclo->estado) === 'ACTIVO')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst(strtolower($ciclo->estado)) }}</span>
                                @endif
                            </td>
                            <td>{{ $ciclo->fecha_inicio ? \Carbon\Carbon::parse($ciclo->fecha_inicio)->format('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $ciclo->fecha_fin ? \Carbon\Carbon::parse($ciclo->fecha_fin)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                    @can('editar_ciclos')
                                        <a href="{{ route('ciclos.edit', $ciclo) }}" class="btn btn-success btn-sm"
                                            title="Editar ciclo">
                                            <i class="bi bi-pencil-square text-white"></i> Editar
                                        </a>

                                        <form action="{{ route('ciclos.destroy', $ciclo) }}" method="POST" class="d-inline"
                                            id="formEliminar{{ $ciclo->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm rounded-0 rounded-end"
                                                title="Enviar a papelera" onclick="confirmarEliminacion({{ $ciclo->id }})">
                                                <i class="bi bi-trash text-white"></i> Eliminar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small fst-italic">Solo lectura</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
@section('js')
    <script>
        $(document).ready(function() {
            if ($('#ciclosTable').length) {
                $('#ciclosTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay datos disponibles en la tabla",
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
        });

        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Enviar a papelera?',
                text: "El ciclo será movido a la papelera.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, enviar',
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
@stop
