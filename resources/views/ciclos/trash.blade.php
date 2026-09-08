@extends('adminlte::page')

@section('title', 'Papelera de Periodos')
{{-- Activamos los plugins configurados globalmente --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Papelera de <b>Periodos</b></h1>
        <div>
            <a href="{{ route('ciclos.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-secondary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-trash3 me-1"></i> Registros en Papelera (Disponibles para Restaurar)
            </h3>
        </div>
        <div class="card-body">
            <table id="trashTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2">#</th>
                        <th class="py-2">Nombre</th>
                        <th class="py-2">Fecha de Eliminación</th>
                        <th style="width: 130px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ciclos as $index => $ciclo)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold text-muted">{{ $ciclo->nombre }}</td>
                            <td>{{ $ciclo->deleted_at ? \Carbon\Carbon::parse($ciclo->deleted_at)->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="text-center">
                                <form action="{{ route('ciclos.restore', $ciclo->id) }}" method="POST" class="d-inline"
                                    id="formRestaurar{{ $ciclo->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" class="btn btn-success btn-sm shadow-sm"
                                        title="Restaurar periodo" onclick="confirmarRestauracion({{ $ciclo->id }})">
                                        <i class="bi bi-arrow-counterclockwise text-white"></i> Restaurar
                                    </button>
                                </form>
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

        function confirmarRestauracion(id) {
            Swal.fire({
                title: '¿Restaurar periodo?',
                text: "El registro volverá a estar activo en el sistema.",
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
    </script>
@stop
