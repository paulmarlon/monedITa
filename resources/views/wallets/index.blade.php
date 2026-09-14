@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">
@section('plugins.SelectBs', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>MonedITa <small>Fondo Eco-Salesiano</small></h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Columna Izquierda: Saldo, Fondo Eco-Salesiano, Transferencias y Acreditación -->
            <div class="col-lg-4 col-md-6">

                <!-- CONTENEDOR COMPACTO SUPERIOR: Fondo Eco-Salesiano y Saldo Actual -->
                <div class="row">
                    <!-- Fondo Eco-Salesiano (Compacto) -->
                    <div class="col-6">
                        <div class="small-box bg-warning mb-3 shadow-sm">
                            <div class="inner p-2 text-dark">
                                <h4 class="mb-0 font-weight-bold">{{ number_format($causaComun->total_acumulado ?? 0, 2) }}
                                </h4>
                                <p class="mb-0" style="font-size: 0.8rem;"><i class="bi bi-recycle text-success"></i>
                                    Fondo
                                    Eco</p>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Actual (Compacto) -->
                    <div class="col-6">
                        <div class="small-box bg-success mb-3 shadow-sm">
                            <div class="inner p-2 text-white">
                                <h4 class="mb-0 font-weight-bold" id="saldoActualText">
                                    {{ number_format($wallet->saldo_actual, 2) }}</h4>
                                <p class="mb-0" style="font-size: 0.8rem;"><i class="bi bi-wallet2"></i> Disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TARJETA EXCLUSIVA DE SUPERVISOR: Acreditar saldo por Tapitas --}}
                @can('acreditar_wallet')
                    <div class="card card-success card-outline mt-2">
                        <div class="card-header py-2">
                            <h3 class="card-title text-sm"><i class="bi bi-coin text-success"></i> Canjear Tapitas (Acreditar)
                            </h3>
                        </div>
                        <form id="formAcreditar" action="{{ route('wallets.acreditar') }}" method="POST">
                            @csrf
                            <div class="card-body py-2">
                                <div class="mb-2">
                                    <label for="receptor_id_acreditar" class="form-label small">Estudiante (Receptor)</label>
                                    @php
                                        $configAcreditar = [
                                            'placeholder' => 'Busca al estudiante...',
                                            'allowClear' => true,
                                        ];
                                    @endphp
                                    <x-adminlte-select-bs id="receptor_id_acreditar" name="receptor_id" :config="$configAcreditar"
                                        required>
                                        <option value="">Selecciona un estudiante...</option>
                                        @foreach ($usuarios as $companero)
                                            <option value="{{ $companero->id }}">
                                                [{{ $companero->registro_universitario }}] ({{ $companero->alias }}) -
                                                {{ $companero->name }}
                                            </option>
                                        @endforeach
                                    </x-adminlte-select-bs>
                                </div>

                                <div class="mb-2">
                                    <label for="tapitasCantidad" class="form-label small">Cantidad de Tapitas</label>
                                    <input type="number" min="1" name="tapitas_cantidad" id="tapitasCantidad"
                                        class="form-control form-control-sm" placeholder="Ej: 150" required
                                        oninput="calcularMoneditas(this.value)">
                                </div>

                                <div class="mb-2">
                                    <label for="montoAcreditar" class="form-label small">Moneditas Equivalentes</label>
                                    <input type="number" step="0.01" min="0.01" name="monto" id="montoAcreditar"
                                        class="form-control form-control-sm" placeholder="0.00" readonly required>
                                </div>
                            </div>
                            @can('acreditar_wallet')
                                <div class="card-footer py-2">
                                    <button type="button" onclick="confirmarTransaccion('acreditar')"
                                        class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check-circle"></i> Acreditar Saldo
                                    </button>
                                </div>
                            @endcan
                        </form>
                    </div>
                @endcan

                <!-- Tarjeta para Transferir a Compañeros -->
                <div class="card card-primary card-outline mt-3">
                    <div class="card-header py-2">
                        <h3 class="card-title text-sm"><i class="bi bi-send text-primary"></i> Enviar Moneditas</h3>
                    </div>
                    <form id="formTransferir" action="{{ route('wallets.transferir') }}" method="POST">
                        @csrf
                        <div class="card-body py-2">
                            <div class="mb-2">
                                <label for="receptor_id" class="form-label small">Compañero (Receptor)</label>
                                @php
                                    $config = [
                                        'placeholder' => 'Escribe el registro, o alias...',
                                        'allowClear' => true,
                                    ];
                                @endphp
                                <x-adminlte-select-bs id="receptor_id" name="receptor_id" :config="$config" required>
                                    <option value="">Selecciona un compañero...</option>
                                    @foreach ($usuarios as $companero)
                                        <option value="{{ $companero->id }}">
                                            [{{ $companero->registro_universitario }}] ({{ $companero->alias }}) -
                                            {{ $companero->name }}
                                        </option>
                                    @endforeach
                                </x-adminlte-select-bs>
                            </div>

                            <div class="mb-2">
                                <label for="monto" class="form-label small">Cantidad</label>
                                <input type="number" step="0.01" min="0.01" name="monto" id="montoTransferir"
                                    class="form-control form-control-sm" placeholder="0.00" required>
                            </div>

                            <div class="mb-2">
                                <label for="observacion" class="form-label small">Motivo / Mensaje</label>
                                <input type="text" name="observacion" id="observacionTransferir"
                                    class="form-control form-control-sm" placeholder="Ej: Pago por tarea o apuesta">
                            </div>
                        </div>
                        <div class="card-footer py-2">
                            <button type="button" onclick="confirmarTransaccion('transferir')"
                                class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-cursor"></i> Transferir Ahora
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Tabla de Historial Compacto (Últimas 5 transacciones interactivas) -->
            <div class="col-lg-8 col-md-6">
                <div class="card mt-4 mt-lg-0">
                    <!-- Único encabezado limpio -->
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 text-sm">
                            <i class="bi bi-clock-history"></i> Historial Reciente
                        </h3>
                        <!-- Botón desplegable adaptativo para modo claro y oscuro -->
                        <button class="btn btn-tool p-0 d-lg-none" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseHistorial" aria-expanded="false" aria-controls="collapseHistorial">
                            <i class="bi bi-chevron-down"></i> Desplegar
                        </button>
                    </div>

                    <!-- Cuerpo colapsable solo en móviles (d-lg-block asegura que en PC grande permanezca visible siempre) -->
                    <div class="collapse d-lg-block" id="collapseHistorial">
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-hover text-nowrap align-middle mb-0"
                                style="cursor: pointer;">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-2">Fecha</th>
                                        <th class="py-2">Movimiento</th>
                                        <th class="py-2 text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transacciones as $tx)
                                        <tr data-bs-toggle="modal" data-bs-target="#modalTx{{ $tx->id }}"
                                            title="Ver detalle completo">
                                            <td class="small">
                                                {{ $tx->created_at ? \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') : 'N/A' }}
                                            </td>
                                            <td>
                                                @if ($tx->emisor_id == auth()->id())
                                                    <span class="badge bg-danger" style="font-size: 0.75rem;"><i
                                                            class="bi bi-arrow-up-right"></i> Enviado</span>
                                                @else
                                                    <span class="badge bg-success" style="font-size: 0.75rem;"><i
                                                            class="bi bi-arrow-down-left"></i> Recibido</span>
                                                @endif
                                                <span class="text-muted ms-1"
                                                    style="font-size: 0.8rem;">({{ $tx->tipo_operacion }})</span>
                                            </td>
                                            <td class="text-end">
                                                <span
                                                    class="fw-bold text-success">{{ number_format($tx->saldo_despues ?? 0, 2) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">Sin movimientos
                                                registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($transacciones->hasPages())
                            <div class="card-footer py-2 d-flex justify-content-center">
                                {{ $transacciones->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALES INDIVIDUALES PARA CADA TRANSACCIÓN DE LA TABLA --}}
    @foreach ($transacciones as $tx)
        <x-adminlte-modal id="modalTx{{ $tx->id }}" title="Detalle de Transacción" theme="info"
            icon="bi bi-info-circle" size="md" v-centered scrollable>
            <div class="p-2">
                <p class="mb-2"><strong>Fecha y Hora:</strong> <span
                        class="text-muted">{{ $tx->created_at ? \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i:s') : 'N/A' }}</span>
                </p>
                <p class="mb-2"><strong>Tipo de Operación:</strong>
                    <span class="badge {{ $tx->tipo_operacion === 'RECICLAJE' ? 'bg-success' : 'bg-info' }}">
                        {{ $tx->tipo_operacion }}
                    </span>
                </p>
                <p class="mb-2"><strong>De (Emisor):</strong>
                    @php
                        $emisorRu = optional($tx->emisor)->registro_universitario ?? 'Sistema';
                        $emisorName = optional($tx->emisor)->alias ?? 'Sistema';
                    @endphp
                    <span class="text-muted">[{{ $emisorRu }}] {{ $emisorName }}</span>
                </p>
                <p class="mb-2"><strong>Para (Receptor):</strong>
                    @php
                        $receptorRu = optional($tx->receptor)->registro_universitario ?? 'Sistema';
                        $receptorName = optional($tx->receptor)->alias ?? 'Sistema';
                    @endphp
                    <span class="text-muted">[{{ $receptorRu }}] {{ $receptorName }}</span>
                </p>
                <p class="mb-2"><strong>Monto de la Operación:</strong>
                    @if ($tx->emisor_id == auth()->id())
                        <span class="text-danger fw-bold">-{{ number_format($tx->monto, 2) }} moneditas</span>
                    @else
                        <span class="text-success fw-bold">+{{ number_format($tx->monto, 2) }} moneditas</span>
                    @endif
                </p>
                <p class="mb-2"><strong>Saldo Posterior:</strong>
                    <span class="text-success fw-bold">{{ number_format($tx->saldo_despues ?? 0, 2) }} moneditas</span>
                </p>
                <hr>
                <p class="mb-0"><strong>Observación / Motivo:</strong><br>
                    <span class="text-muted italic">{{ $tx->observacion ?: 'Sin observaciones registradas.' }}</span>
                </p>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button theme="secondary" label="Cerrar" data-bs-dismiss="modal" />
            </x-slot>
        </x-adminlte-modal>
    @endforeach
@stop

@section('js')
    <script>
        function confirmarTransaccion(tipo) {
            let formId = tipo === 'transferir' ? 'formTransferir' : 'formAcreditar';
            let montoId = tipo === 'transferir' ? 'montoTransferir' : 'montoAcreditar';
            let montoVal = document.getElementById(montoId).value;

            if (!montoVal || montoVal <= 0) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Por favor, introduce un monto válido.',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }

            let titulo = tipo === 'transferir' ? '¿Confirmar transferencia?' : '¿Confirmar acreditación de saldo?';
            let texto = tipo === 'transferir' ? 'Se enviarán ' + montoVal + ' moneditas a tu compañero.' :
                'Se acreditarán ' + montoVal + ' moneditas por reciclaje.';

            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, realizar',
                cancelButtonText: 'Cancelar',
                background: '#343a40',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }

        function calcularMoneditas(tapitas) {
            let montoInput = document.getElementById('montoAcreditar');
            if (tapitas && !isNaN(tapitas) && tapitas > 0) {
                montoInput.value = parseFloat(tapitas).toFixed(2);
            } else {
                montoInput.value = '';
            }
        }

        $(document).ready(function() {
            @if (session('success') || session('mensaje'))
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: "{!! addslashes(session('success') ?? session('mensaje')) !!}",
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    toast: true,
                    background: '#343a40',
                    color: '#ffffff'
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    position: 'top-end',
                    icon: 'error',
                    title: "{!! addslashes($errors->first()) !!}",
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    toast: true,
                    background: '#dc3545',
                    color: '#ffffff'
                });
            @endif
        });
    </script>
@stop
