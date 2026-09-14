@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Papelera de <b>Equipos</b></h1>
        <a href="{{ route('teams.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al Listado
        </a>
    </div>
@stop

@section('content')
    <div class="card card-secondary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-trash3 me-1"></i> Equipos Eliminados (SoftDeletes)
            </h3>
        </div>
        <div class="card-body">
            <table id="trashTeamsTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2" style="width: 50px;">#</th>
                        <th class="py-2">Nombre del Equipo</th>
                        <th class="py-2">Ciclo Asociado</th>
                        <th class="py-2">Fecha de Eliminación</th>
                        <th style="width: 120px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teams as $index => $team)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold text-danger">{{ $team->nombre }}</td>
                            <td>{{ $team->ciclo->nombre ?? 'N/D' }}</td>
                            <td>{{ $team->deleted_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <form action="{{ route('teams.restore', $team->id) }}" method="POST" class="d-inline"
                                    id="formRestaurar{{ $team->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" class="btn btn-success btn-sm shadow-sm" title="Restaurar equipo"
                                        onclick="confirmarRestauracion({{ $team->id }})">
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
            if ($('#trashTeamsTable').length) {
                $('#trashTeamsTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "emptyTable": "La papelera está vacía",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "search": "Buscar:",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    }
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
                title: '¿Restaurar equipo?',
                text: "El equipo volverá al listado activo.",
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
