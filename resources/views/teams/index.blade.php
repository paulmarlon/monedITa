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
        <h1>Gestión de <b>Equipos</b></h1>
        <div>
            <a href="{{ route('teams.trash') }}" class="btn btn-outline-secondary me-2 btn-sm shadow-sm">
                <i class="bi bi-trash3 me-1"></i> Papelera
            </a>
            <a href="{{ route('teams.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Crear Equipo
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-people me-1"></i> Listado General de Equipos Activos
            </h3>
        </div>
        <div class="card-body">
            <table id="teamsTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2" style="width: 50px;">#</th>
                        <th class="py-2" style="width: 70px;" class="text-center">Logo</th>
                        <th class="py-2">Nombre del Equipo</th>
                        <th class="py-2">Ciclo / Periodo</th>
                        <th style="width: 120px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teams as $index => $team)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-center">
                                @if ($team->logo)
                                    <img src="{{ $team->logo }}" alt="Logo" class="rounded-circle shadow-sm"
                                        style="width: 35px; height: 35px; object-fit: cover;">
                                @else
                                    <span class="badge bg-secondary">Sin logo</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $team->nombre }}</td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $team->ciclo->nombre ?? 'Sin Ciclo' }}</span>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                    @can('editar_teams')
                                        {{-- Usamos el permiso exacto de tu Seeder --}}
                                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-success btn-sm"
                                            title="Editar equipo">
                                            <i class="bi bi-pencil-square text-white"></i> Editar
                                        </a>
                                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="d-inline"
                                            id="formEliminar{{ $team->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm rounded-0 rounded-end"
                                                title="Enviar a papelera" onclick="confirmarEliminacion({{ $team->id }})">
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
            if ($('#teamsTable').length) {
                $('#teamsTable').DataTable({
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

            @if (session('success') || session('mensaje'))
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ session('success') ?? session('mensaje') }}',
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
                text: "El equipo será movido a la papelera.",
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
