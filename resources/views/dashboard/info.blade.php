@extends('adminlte::page')

@section('title', 'Panel de Información')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
        <div>
            <h1 class="h4 fw-bold mb-0">Panel de Estadísticas</h1>
            <p class="text-muted small mb-0">Resumen general del periodo en curso</p>
        </div>
        @if (isset($cicloActivo))
            <div
                class="bg-dark text-white px-2.5 py-1.5 rounded-2 small fw-medium d-flex align-items-center gap-2 shadow-sm">
                <span class="spinner-grow spinner-grow-sm bg-success rounded-circle" style="width: 8px; height: 8px;"></span>
                Ciclo Activo: <span class="fw-bold">{{ $cicloActivo->nombre }}</span>
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
            <!-- Sección de Métricas Principales (Small Boxes) -->
            <div class="row g-3 mb-3">
                <!-- Small Box de Reciclaje Principal -->
                <div class="col-12 col-lg-6">
                    <div class="position-relative overflow-hidden h-100">
                        <x-adminlte-ribbon label="ACTIVO" theme="success" size="sm" />
                        <x-adminlte-small-box title="{{ number_format($totalTapitas, 2) }}"
                            text="Total de Tapitas Recaudadas" icon="bi bi-recycle" theme="success" url="#"
                            url-text="Ver reporte de reciclaje" class="shadow-sm h-100 mb-0 compact-small-box">
                        </x-adminlte-small-box>
                    </div>
                </div>

                <!-- Panel Lateral con Small Boxes de Estadísticas del Sistema -->
                <div class="col-12 col-lg-6 d-flex flex-column justify-content-between gap-2">
                    <x-adminlte-small-box title="{{ count($equiposRanking) }}" text="Equipos Registrados"
                        icon="bi bi-trophy" theme="warning" url="#" url-text="Ver todos los equipos"
                        class="shadow-sm mb-0 compact-small-box">
                    </x-adminlte-small-box>

                    <x-adminlte-small-box title="Top 5" text="Minijuegos Activos" icon="bi bi-controller" theme="info"
                        url="#" url-text="Ver puntajes" class="shadow-sm mb-0 compact-small-box">
                    </x-adminlte-small-box>
                </div>
            </div>

            <!-- Sección de Progreso con Progress Groups (Compacto) -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <x-adminlte-card title="Indicadores de Rendimiento del Ciclo" theme="teal" theme-mode="outline"
                        icon="bi bi-graph-up-arrow" class="shadow-sm mb-0">
                        <div class="row g-3">
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
                                <x-adminlte-progress-group label="Actividad en Minijuegos" :value="count($mejoresPuntajesJuegos)"
                                    :max="5" theme="warning" class="mb-0">
                                    <b>{{ count($mejoresPuntajesJuegos) }}</b> / 5 Jugadores
                                </x-adminlte-progress-group>
                            </div>
                        </div>
                    </x-adminlte-card>
                </div>
            </div>

            <!-- Grid de Rankings (Compacto con Small Boxes para Equipos) -->
            <div class="row g-3">

                <!-- Ranking de Equipos (Top General) -->
                <div class="col-12 col-lg-6">
                    <x-adminlte-card title="Ranking de Equipos (Top General)" theme="primary" theme-mode="outline"
                        icon="bi bi-trophy-fill" class="shadow-sm h-100 mb-0">

                        <div class="d-flex flex-column gap-2" style="padding: 0.25rem 0;">
                            @forelse($equiposRanking->sortByDesc('total_recaudado')->values() as $index => $team)
                                @php
                                    $logoUrl = $team->logo ? asset('storage/' . $team->logo) : null;
                                    $teamNombre = $team->nombre ?? 'Equipo';

                                    $themes = ['success', 'primary', 'info', 'warning', 'danger'];
                                    $currentTheme = $themes[$index] ?? 'secondary';

                                    $maxRecaudado =
                                        $equiposRanking->max('total_recaudado') > 0
                                            ? $equiposRanking->max('total_recaudado')
                                            : 100;
                                    $porcentajeTeam = min(
                                        round((($team->total_recaudado ?? 0) / $maxRecaudado) * 100),
                                        100,
                                    );
                                @endphp

                                <div class="p-2.5 rounded border shadow-sm"
                                    style="background-color: var(--bs-tertiary-bg) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark px-1.5 py-1"
                                                style="font-size: 0.7rem;">#{{ $loop->iteration }}</span>

                                            @if ($logoUrl)
                                                <img src="{{ $logoUrl }}" alt="{{ $teamNombre }}"
                                                    class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0"
                                                    style="width: 38px; height: 38px; object-fit: cover;"
                                                    title="Equipo: {{ $teamNombre }}">
                                            @else
                                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0 border border-white"
                                                    style="width: 38px; height: 38px; font-size: 0.75rem;"
                                                    title="Equipo: {{ $teamNombre }}">
                                                    {{ strtoupper(substr($teamNombre, 0, 2)) }}
                                                </div>
                                            @endif

                                            <span class="fw-bold text-body ms-1" style="font-size: 0.9rem;">
                                                {{ $teamNombre }}
                                            </span>
                                        </div>

                                        <span class="badge bg-primary px-2 py-1"
                                            style="font-size: 0.8rem;">{{ number_format($team->total_recaudado ?? 0, 0) }}
                                            Pts</span>
                                    </div>

                                    <x-adminlte-progress :theme="$currentTheme" :value="$porcentajeTeam" size="xs" animated
                                        with-label class="mb-0">
                                        <x-slot name="labelSlot"><span style="font-size: 0.7rem; font-weight: 600;">Progreso
                                                respecto al líder</span></x-slot>
                                    </x-adminlte-progress>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">
                                    No hay equipos registrados en este ciclo.
                                </div>
                            @endforelse
                        </div>
                    </x-adminlte-card>
                </div>

                <!-- Mejores Puntajes en Minijuegos (Top 5) -->
                <div class="col-12 col-lg-6">
                    <x-adminlte-card title="Mejores Puntajes - Juegos individuales (Top 5)" theme="info"
                        theme-mode="outline" icon="bi bi-controller" class="shadow-sm h-100 mb-0">

                        <div class="d-flex flex-column gap-2" style="padding: 0.25rem 0;">
                            @forelse($mejoresPuntajesJuegos->sortByDesc('puntaje')->values() as $index => $puntaje)
                                @php
                                    $maxPuntaje =
                                        $mejoresPuntajesJuegos->max('puntaje') > 0
                                            ? $mejoresPuntajesJuegos->max('puntaje')
                                            : 100;
                                    $porcentaje = min(round(($puntaje->puntaje / $maxPuntaje) * 100), 100);

                                    $themes = ['success', 'primary', 'info', 'warning', 'teal'];
                                    $currentTheme = $themes[$index] ?? 'sky';

                                    $icons = [
                                        'bi bi-award-fill text-warning',
                                        'bi bi-star-fill text-info',
                                        'bi bi-fire text-danger',
                                        'bi bi-lightning-fill text-primary',
                                        'bi bi-controller text-success',
                                    ];
                                    $currentIcon = $icons[$index] ?? 'bi bi-person-badge';

                                    $userAvatarUrl =
                                        isset($puntaje->user_avatar) && $puntaje->user_avatar
                                            ? asset('storage/' . $puntaje->user_avatar)
                                            : null;
                                    $teamLogoUrl =
                                        isset($puntaje->team_logo) && $puntaje->team_logo
                                            ? asset('storage/' . $puntaje->team_logo)
                                            : null;

                                    $teamNombre = $puntaje->team_nombre ?? 'Equipo';
                                    $userAlias = $puntaje->alias ?? 'Usuario';
                                @endphp

                                <div class="p-2.5 rounded border shadow-sm"
                                    style="background-color: var(--bs-tertiary-bg) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark px-1.5 py-1"
                                                style="font-size: 0.7rem;">#{{ $loop->iteration }}</span>

                                            @if ($teamLogoUrl)
                                                <img src="{{ $teamLogoUrl }}" alt="{{ $teamNombre }}"
                                                    class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0"
                                                    style="width: 24px; height: 24px; object-fit: cover;"
                                                    title="Equipo: {{ $teamNombre }}">
                                            @else
                                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0 border border-white"
                                                    style="width: 24px; height: 24px; font-size: 0.55rem;"
                                                    title="Equipo: {{ $teamNombre }}">
                                                    {{ strtoupper(substr($teamNombre, 0, 2)) }}
                                                </div>
                                            @endif

                                            <span class="fw-bold text-body ms-1" style="font-size: 0.85rem;">
                                                {{ $teamNombre }}
                                            </span>
                                            <span class="text-muted" style="font-size: 0.8rem;">•</span>
                                            <span class="fw-bold text-body" style="font-size: 0.85rem;">
                                                {{ $userAlias }}
                                            </span>

                                            @if ($userAvatarUrl)
                                                <img src="{{ $userAvatarUrl }}" alt="{{ $userAlias }}"
                                                    class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0"
                                                    style="width: 32px; height: 32px; object-fit: cover;"
                                                    title="Usuario: {{ $userAlias }}">
                                            @else
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 border border-white"
                                                    style="width: 32px; height: 32px; font-size: 0.7rem;"
                                                    title="Usuario: {{ $userAlias }}">
                                                    <i class="{{ $currentIcon }}" style="font-size: 0.8rem;"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Badge destacado con el puntaje exacto a la derecha (igual que en Equipos) -->
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"
                                                style="font-size: 0.65rem;">{{ $puntaje->juego_titulo }}</span>
                                            <span class="badge bg-info px-2 py-1"
                                                style="font-size: 0.8rem;">{{ number_format($puntaje->puntaje ?? 0, 0) }}
                                                Pts</span>
                                        </div>
                                    </div>

                                    <x-adminlte-progress :theme="$currentTheme" :value="$porcentaje" size="xs" animated
                                        with-label class="mb-0">
                                        <x-slot name="labelSlot"><span
                                                style="font-size: 0.7rem; font-weight: 600;">Progreso respecto al
                                                líder</span></x-slot>
                                    </x-adminlte-progress>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">
                                    Aún no hay registros de puntajes en este ciclo.
                                </div>
                            @endforelse
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
            padding: 0.75rem !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .inner {
            padding: 0.2rem 0.5rem !important;
        }

        .compact-small-box .inner h3 {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .inner p {
            font-size: 0.8rem !important;
            margin-bottom: 0 !important;
        }

        .compact-small-box .icon {
            top: -5px !important;
            right: 10px !important;
            font-size: 2.5rem !important;
            opacity: 0.2;
        }

        .compact-small-box .small-box-footer {
            padding: 2px 0 !important;
            font-size: 0.7rem !important;
        }
    </style>
@endpush
