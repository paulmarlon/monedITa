@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">
@stop

{{-- Activamos los plugins necesarios de DataTables --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesButtons', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Historial y Ranking de <b>Puntajes</b></h1>
        <div>
            <a href="{{ route('juegos.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Catálogo de Juegos
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Tabla de Puntajes / Partidas -->
        <div class="row">
            <div class="col-12">
                <div class="card card-warning card-outline shadow">
                    <div class="card-header">
                        <h3 class="card-title text-warning">
                            <i class="bi bi-trophy me-1"></i> Registro histórico de partidas y puntajes de estudiantes
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="puntajesTable"
                                class="table table-bordered table-striped table-hover table-sm w-100 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-2" style="width: 10px">#</th>
                                        <th class="py-2">Estudiante</th>
                                        <th class="py-2">Minijuego</th>
                                        <th class="py-2">Puntaje Obtenido</th>
                                        <th class="py-2">Fecha y Hora de Partida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($puntajes as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-semibold">
                                                <div class="d-flex align-items-center">
                                                    @if ($item->user && $item->user->avatar)
                                                        {{-- Avatar del usuario --}}
                                                        <img src="{{ asset('storage/' . $item->user->avatar) }}"
                                                            alt="Avatar" class="rounded-circle me-2"
                                                            style="width: 36px; height: 36px; object-fit: cover;">
                                                    @else
                                                        {{-- Avatar por defecto --}}
                                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                                            style="width: 36px; height: 36px; font-size: 14px;">
                                                            <i class="bi bi-person"></i>
                                                        </div>
                                                    @endif

                                                    <div>
                                                        <div class="text-dark">
                                                            {{ $item->user->alias ?? 'Usuario Eliminado' }}</div>

                                                        {{-- Información del Equipo / Team --}}
                                                        @if ($item->user && $item->user->team)
                                                            <span
                                                                class="badge bg-light text-dark border d-inline-flex align-items-center mt-1">
                                                                @if ($item->user->team->logo)
                                                                    <img src="{{ asset('storage/' . $item->user->team->logo) }}"
                                                                        alt="Logo Team" class="rounded-circle me-1"
                                                                        style="width: 14px; height: 14px; object-fit: cover;">
                                                                @else
                                                                    <i class="bi bi-shield-fill text-warning me-1"
                                                                        style="font-size: 10px;"></i>
                                                                @endif
                                                                {{ $item->user->team->nombre }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted" style="font-size: 11px;">Sin
                                                                equipo</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    {{ $item->juego->titulo ?? 'Juego no disponible' }}
                                                </span>
                                            </td>
                                            {{-- Atributo data-order para ordenamiento matemático exacto --}}
                                            <td data-order="{{ $item->puntaje }}">
                                                <span class="fw-bold text-success">
                                                    {{ number_format($item->puntaje) }} pts
                                                </span>
                                            </td>
                                            <td>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <!-- El contenido vacío lo gestiona DataTables -->
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
            if ($('#puntajesTable').length) {
                $('#puntajesTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    // Ordenar estrictamente por la columna 3 (Puntaje) de mayor a menor
                    order: [
                        [3, 'desc']
                    ],
                    pageLength: 10,
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay registros de puntajes todavía",
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
                        }
                    },
                    // DOM simplificado sin la sección de botones (B)
                    dom: '<"row mx-0 border-bottom py-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mx-0 pt-2"<"col-sm-6"i><"col-sm-6"p>>',
                    buttons: [] // Sin botones de exportación
                });
            }
        });
    </script>
@endsection
