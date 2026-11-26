@php
    $maxPuntaje = ($puntajes->max('puntaje') ?? 0) > 0 ? $puntajes->max('puntaje') : 100;
    $themes = ['success', 'primary', 'info', 'warning', 'teal'];
@endphp

<div class="d-flex flex-column" style="gap: 0.35rem;">
    @forelse($puntajes as $index => $puntaje)
        @php
            $porcentaje = min(round((($puntaje->puntaje ?? 0) / $maxPuntaje) * 100), 100);
            $currentTheme = $themes[$index] ?? 'secondary';
            $teamNombre = $puntaje->team_nombre ?? 'Equipo';
            $userAlias = $puntaje->alias ?? 'Usuario';
        @endphp

        <div class="px-2 py-1 rounded border" style="background-color: var(--bs-tertiary-bg) !important;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center min-w-0">
                    <span class="badge bg-dark me-2" style="font-size: 0.65rem;">#{{ $loop->iteration }}</span>

                    @if ($puntaje->team_logo_url)
                        <img src="{{ $puntaje->team_logo_url }}" alt="{{ $teamNombre }}"
                            class="rounded-circle border me-1"
                            style="width: 20px; height: 20px; object-fit: cover; flex-shrink: 0;">
                    @endif

                    <span class="font-weight-bold text-truncate" style="font-size: 0.78rem;">
                        {{ $teamNombre }} <span class="text-muted font-weight-normal">•</span> {{ $userAlias }}
                    </span>
                </div>

                <div class="d-flex align-items-center ms-2" style="white-space: nowrap;">
                    @if (!empty($mostrarJuego))
                        <span class="badge bg-secondary me-1"
                            style="font-size: 0.62rem;">{{ $puntaje->juego_titulo }}</span>
                    @endif
                    <span class="badge bg-info" style="font-size: 0.72rem;">
                        {{ number_format($puntaje->puntaje ?? 0, 0) }} Pts
                    </span>
                </div>
            </div>

            <x-adminlte-progress :theme="$currentTheme" :value="$porcentaje" size="xs" class="mb-0" />
        </div>
    @empty
        <div class="text-center text-muted py-3 small">
            Aún no hay registros de puntajes en este ciclo.
        </div>
    @endforelse
</div>
