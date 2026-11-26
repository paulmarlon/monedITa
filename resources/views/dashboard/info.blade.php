@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
            <h1 class="h5 font-weight-bold mb-0">Panel de Estadísticas</h1>
            <p class="text-muted small mb-0">Resumen general del periodo en curso</p>
        </div>
        @if (isset($cicloActivo))
            <div
                class="bg-dark text-white px-2 py-1 rounded small font-weight-medium d-flex align-items-center shadow-sm mt-2 mt-md-0">
                <span class="spinner-grow spinner-grow-sm bg-success rounded-circle me-2"
                    style="width: 8px; height: 8px;"></span>
                Ciclo Activo: <span class="font-weight-bold ms-1">{{ $cicloActivo->nombre }}</span>
            </div>
        @endif
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">

        @if (isset($error))
            <x-adminlte-alert theme="warning" title="Atención" icon="bi bi-exclamation-triangle-fill" class="mb-3">
                {{ $error }}
            </x-adminlte-alert>
        @else
            @php
                $totalJuegos = isset($juegos) ? $juegos->count() : 0;
                $juegosConPuntajes = isset($rankingsPorJuego)
                    ? collect($rankingsPorJuego)->filter(fn($item) => $item['puntajes']->isNotEmpty())->count()
                    : 0;
                $maxRecaudado =
                    $equiposRanking->max('total_recaudado') > 0 ? $equiposRanking->max('total_recaudado') : 100;
                $themes = ['success', 'primary', 'info', 'warning', 'danger'];
            @endphp

            <!-- Métricas Principales Compactas -->
            <div class="row g-2 mb-2">
                <div class="col-12 col-lg-6">
                    <div class="position-relative overflow-hidden h-100">
                        <x-adminlte-ribbon label="ACTIVO" theme="success" size="sm" />
                        <x-adminlte-small-box title="{{ number_format($totalTapitas, 2) }}"
                            text="Total de Tapitas Recaudadas" icon="bi bi-recycle" theme="success"
                            url="{{ route('wallets.index') }}" url-text="Ver monedero"
                            class="shadow-sm h-100 mb-0 compact-small-box">
                        </x-adminlte-small-box>
                    </div>
                </div>

                <div class="col-12 col-lg-6 d-flex flex-column justify-content-between" style="gap: 0.4rem;">
                    <x-adminlte-small-box title="{{ count($equiposRanking) }}" text="Equipos Registrados"
                        icon="bi bi-trophy" theme="warning" url="{{ route('teams.index') }}" url-text="Ver equipos"
                        class="shadow-sm mb-0 compact-small-box">
                    </x-adminlte-small-box>

                    <x-adminlte-small-box title="{{ $totalJuegos }}"
                        text="Juegos en Catálogo ({{ $juegosConPuntajes }} con puntajes)" icon="bi bi-controller"
                        theme="info" url="{{ route('juegos.index') }}" url-text="Ver juegos"
                        class="shadow-sm mb-0 compact-small-box">
                    </x-adminlte-small-box>
                </div>
            </div>

            <!-- Indicadores de Rendimiento del Ciclo -->
            <div class="row g-2 mb-2">
                <div class="col-12">
                    <x-adminlte-card title="Indicadores de Rendimiento del Ciclo" theme="teal" theme-mode="outline"
                        icon="bi bi-graph-up-arrow" class="shadow-sm mb-0 compact-card">
                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <x-adminlte-progress-group label="Meta de Reciclaje Global" :value="min($totalTapitas, 1000)"
                                    :max="1000" theme="success" class="mb-0">
                                    <b>{{ number_format($totalTapitas, 0) }}</b> / 1000
                                </x-adminlte-progress-group>
                            </div>
                            <div class="col-12 col-md-4">
                                <x-adminlte-progress-group label="Participación de Equipos" :value="count($equiposRanking)"
                                    :max="10" theme="primary" class="mb-0">
                                    <b>{{ count($equiposRanking) }}</b> de 10
                                </x-adminlte-progress-group>
                            </div>
                            <div class="col-12 col-md-4">
                                <x-adminlte-progress-group label="Juegos con Actividad en el Ciclo" :value="$juegosConPuntajes"
                                    :max="max($totalJuegos, 1)" theme="warning" class="mb-0">
                                    <b>{{ $juegosConPuntajes }}</b> / {{ $totalJuegos }} juegos
                                </x-adminlte-progress-group>
                            </div>
                        </div>
                    </x-adminlte-card>
                </div>
            </div>

            <!-- Rankings: Equipos + Pestañas Compactas por Juego -->
            <div class="row g-2">
                <!-- Ranking de Equipos -->
                <div class="col-12 col-lg-6">
                    <x-adminlte-card title="Ranking de tapITas por Equipos" theme="primary" theme-mode="outline"
                        icon="bi bi-trophy-fill" class="shadow-sm h-100 mb-0 compact-card">
                        <div class="d-flex flex-column" style="gap: 0.35rem;">
                            @forelse($equiposRanking->sortByDesc('total_recaudado')->values() as $index => $team)
                                @php
                                    $teamNombre = $team->nombre ?? 'Equipo';
                                    $porcentajeTeam = min(
                                        round((($team->total_recaudado ?? 0) / $maxRecaudado) * 100),
                                        100,
                                    );
                                    $currentTheme = $themes[$index] ?? 'secondary';
                                @endphp
                                <div class="px-2 py-1 rounded border"
                                    style="background-color: var(--bs-tertiary-bg) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center min-w-0">
                                            <span class="badge bg-dark me-2"
                                                style="font-size: 0.65rem;">#{{ $loop->iteration }}</span>
                                            @if ($team->logo_url)
                                                <img src="{{ $team->logo_url }}" alt="{{ $teamNombre }}"
                                                    class="rounded-circle border me-2"
                                                    style="width: 24px; height: 24px; object-fit: cover; flex-shrink: 0;">
                                            @else
                                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center font-weight-bold me-2"
                                                    style="width: 24px; height: 24px; font-size: 0.6rem; flex-shrink: 0;">
                                                    {{ strtoupper(substr($teamNombre, 0, 2)) }}
                                                </div>
                                            @endif
                                            <span class="font-weight-bold text-truncate"
                                                style="font-size: 0.82rem;">{{ $teamNombre }}</span>
                                        </div>
                                        <span class="badge bg-primary ms-2"
                                            style="font-size: 0.72rem; white-space: nowrap;">
                                            {{ number_format($team->total_recaudado ?? 0, 0) }} tapITas
                                        </span>
                                    </div>
                                    <x-adminlte-progress :theme="$currentTheme" :value="$porcentajeTeam" size="xs"
                                        class="mb-0" />
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">No hay equipos registrados en este ciclo.
                                </div>
                            @endforelse
                        </div>
                    </x-adminlte-card>
                </div>

                <!-- Mejores Puntajes por Juego (Pestañas en una sola tarjeta compacta) -->
                <div class="col-12 col-lg-6">
                    <x-adminlte-card title="Mejores Puntajes por Juego (Ciclo Activo)" theme="info" theme-mode="outline"
                        icon="bi bi-controller" class="shadow-sm h-100 mb-0 compact-card">

                        <ul class="nav nav-pills nav-fill mb-2 gap-1" id="juegoTabs" role="tablist"
                            style="font-size: 0.75rem;">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-2" id="tab-todos" data-bs-toggle="tab"
                                    data-bs-target="#pane-todos" type="button" role="tab">
                                    Todos ({{ $mejoresPuntajesJuegos->count() }})
                                </button>
                            </li>
                            @foreach ($juegos as $juego)
                                @php $listaJuego = $rankingsPorJuego[$juego->id]['puntajes'] ?? collect(); @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-2" id="tab-juego-{{ $juego->id }}"
                                        data-bs-toggle="tab" data-bs-target="#pane-juego-{{ $juego->id }}"
                                        type="button" role="tab">
                                        {{ $juego->titulo }} ({{ $listaJuego->count() }})
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" id="juegoTabsContent">
                            {{-- Pestaña Todos --}}
                            <div class="tab-pane fade show active" id="pane-todos" role="tabpanel">
                                @include('dashboard.partials.ranking-puntajes-lista', [
                                    'puntajes' => $mejoresPuntajesJuegos,
                                    'mostrarJuego' => true,
                                ])
                            </div>

                            {{-- Una pestaña por cada juego en BD --}}
                            @foreach ($juegos as $juego)
                                <div class="tab-pane fade" id="pane-juego-{{ $juego->id }}" role="tabpanel">
                                    @include('dashboard.partials.ranking-puntajes-lista', [
                                        'puntajes' => $rankingsPorJuego[$juego->id]['puntajes'] ?? collect(),
                                        'mostrarJuego' => false,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </x-adminlte-card>
                </div>
            </div>
        @endif

    </div>
@stop

@push('css')
    <style>
        .compact-small-box .small-box {
            padding: 0.55rem 0.65rem !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .inner {
            padding: 0.1rem 0.25rem !important;
        }

        .compact-small-box .inner h3 {
            font-size: 1.35rem !important;
            font-weight: 700 !important;
            line-height: 1.15 !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .inner p {
            font-size: 0.75rem !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .icon {
            top: 2px !important;
            right: 8px !important;
            font-size: 2rem !important;
            opacity: 0.2;
        }

        .compact-small-box .small-box-footer {
            padding: 1px 0 !important;
            font-size: 0.68rem !important;
        }

        .compact-card .card-header {
            padding: 0.45rem 0.75rem !important;
        }

        .compact-card .card-body {
            padding: 0.55rem 0.75rem !important;
        }
    </style>
@endpush
