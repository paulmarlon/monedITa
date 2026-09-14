@extends('adminlte::page')

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
<link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/don bosco.png') }}">

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><b>Panel Principal</b></h1>
    </div>
@stop

@section('content')
    <div class="row justify-content-center py-5">
        <div class="col-md-8 text-center">
            <div class="card card-outline card-primary shadow-sm border-0 py-5">
                <div class="card-body">
                    <!-- Contenedor circular adaptativo para el logo al medio -->
                    <div class="mb-4 d-inline-block p-4 rounded-circle border border-primary shadow-sm bg-body">
                        <img src="{{ asset('storage/usb/don bosco.png') }}" alt="Logo Institucional"
                            style="height: 100px; width: 100px; object-fit: cover;" class="rounded-circle img-fluid">
                    </div>

                    <h1 class="fw-bold text-body mb-3 display-4">¡Hola, Mundo!</h1>

                    <p class="text-muted fs-5 px-md-5 mb-4">
                        <br>
                        Bienvenido a <b>{{ $configGlobal->nombre ?? 'tapITa' }}</b>.
                        <br>
                        <b>{{ $configGlobal->descripcion ?? 'Fondo Eco Salesiano' }}.</b>
                    </p>

                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('wallets.index') }}" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-wallet2 me-1"></i> Ir a mi Billetera
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Estilos personalizados si los requieres --}}
@stop

@section('js')
    <script>
        console.log("Panel Moned-IT-a cargado con éxito.");
    </script>
@stop
