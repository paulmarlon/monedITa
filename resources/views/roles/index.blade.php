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
        <h1>Gestión de <b>Roles</b></h1>
        <div>
            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Crear Nuevo Rol
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-shield-lock me-1"></i> Roles del Sistema (Spatie)
            </h3>
        </div>

        <div class="card-body">
            <table id="rolesTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2" style="width: 10px">#</th>
                        <th class="py-2">Nombre del Rol</th>
                        <th style="width: 120px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $index => $role)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-info text-dark fs-6">{{ $role->name }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                    {{-- Botón para gestionar Permisos del Rol --}}
                                    <a href="{{ route('roles.permissions', $role->id) }}" class="btn btn-warning btn-sm"
                                        title="Asignar permisos al rol">
                                        <i class="bi bi-key-fill text-white"></i> Permisos
                                    </a>

                                    {{-- Botón para Editar el Rol (Nombre, etc.) --}}
                                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-success btn-sm"
                                        title="Editar nombre del rol">
                                        <i class="bi bi-pencil-square text-white"></i> Editar
                                    </a>

                                    {{-- Botón para Eliminar --}}
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline"
                                        id="formEliminar{{ $role->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger btn-sm rounded-0 rounded-end"
                                            title="Eliminar rol" onclick="confirmarEliminacion({{ $role->id }})">
                                            <i class="bi bi-trash text-white"></i> Eliminar
                                        </button>
                                    </form>
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
            if ($('#rolesTable').length) {
                $('#rolesTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay roles disponibles en la tabla",
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

            // Soporte tanto para 'success' como para 'mensaje' enviados desde el controlador
            @if (session('success') || session('mensaje'))
                Swal.fire({
                    position: 'top-end',
                    icon: '{{ session('icon', 'success') }}',
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
                title: '¿Estás seguro?',
                text: "El rol será eliminado permanentemente del sistema.",
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
@stop
