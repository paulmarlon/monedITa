@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña validando Supabase --}}
@section('adminlte_css_pre')
    @php
        $faviconUrl =
            $configGlobal && !empty($configGlobal->logo)
                ? (str_starts_with($configGlobal->logo, 'http')
                    ? $configGlobal->logo
                    : asset('storage/' . $configGlobal->logo))
                : asset('usb/don bosco.png');
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}" type="image/png">
@stop

{{-- Activamos los plugins configurados globalmente --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Auditoría de <b>Sesiones y Accesos Activos</b></h1>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline shadow">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-shield-alt me-1"></i> Control de IPs y Dispositivos Conectados
            </h3>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <table id="sessionsTable" class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="py-2">#</th>
                        <th class="py-2">Usuario</th>
                        <th class="py-2">Dirección IP</th>
                        <th class="py-2">Navegador / Dispositivo (User Agent)</th>
                        <th class="py-2">Última Actividad</th>
                        <th style="width: 120px" class="text-center py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sesiones as $index => $sesion)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $sesion->user_name ?? 'Invitado / No autenticado' }}</strong><br>
                                <small class="text-muted">{{ $sesion->user_email }}</small>
                            </td>
                            <td><code>{{ $sesion->ip_address }}</code></td>
                            <td>
                                <small class="text-break" style="max-width: 300px; display: inline-block;">
                                    {{ $sesion->user_agent }}
                                </small>
                            </td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($sesion->last_activity)->diffForHumans() }}</td>
                            <td class="text-center">
                                @if ($sesion->session_id !== request()->session()->getId())
                                    <form action="{{ route('admin.sessions.destroy', $sesion->session_id) }}" method="POST"
                                        class="d-inline" id="formExpulsar{{ $index }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger btn-sm" title="Expulsar usuario"
                                            onclick="confirmarExpulsion({{ $index }})">
                                            <i class="fas fa-sign-out-alt text-white"></i> Expulsar
                                        </button>
                                    </form>
                                @else
                                    <span class="badge bg-success">Tu sesión actual</span>
                                @endif
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
            if ($('#sessionsTable').length) {
                $('#sessionsTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay sesiones activas registradas en la base de datos",
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

        function confirmarExpulsion(index) {
            Swal.fire({
                title: '¿Expulsar usuario?',
                text: "Se cerrará la sesión de este usuario de forma inmediata.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, expulsar',
                cancelButtonText: 'Cancelar',
                background: '#343a40',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formExpulsar' + index).submit();
                }
            });
        }
    </script>
@stop
