@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Historial de <b>Accesos y Auditoría</b></h1>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history me-1"></i> Registro Permanente de Entradas al Sistema
            </h3>
        </div>
        <div class="card-body">
            <table id="historialTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2">#</th>
                        <th class="py-2">Usuario</th>
                        <th class="py-2">Dirección IP</th>
                        <th class="py-2">Navegador / Dispositivo</th>
                        <th class="py-2">Fecha y Hora de Acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historial as $index => $log)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $log->user_name ?? 'Usuario desconocido' }}</strong><br>
                                <small class="text-muted">{{ $log->user_email }}</small>
                            </td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td>
                                <small class="text-break" style="max-width: 300px; display: inline-block;">
                                    {{ $log->user_agent }}
                                </small>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
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
            if ($('#historialTable').length) {
                $('#historialTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay registros en el historial",
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
        });
    </script>
@stop
