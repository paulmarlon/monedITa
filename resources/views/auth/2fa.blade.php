@php
    if (isset($configGlobal) && !empty($configGlobal->logo)) {
        config(['adminlte.logo_img' => $configGlobal->logo]);
    }
@endphp

@section('title', 'Verificación 2FA - ' . ($configGlobal->nombre ?? '@tech'))

@section('adminlte_css_pre')
    <link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/bosco.png') }}" type="image/png">
@stop

@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Verificación en dos pasos (2FA)')

@section('auth_body')
    <p class="text-muted small mb-3">
        Ingresa el código de 6 dígitos enviado a tu correo
        @if (!empty($emailMasked))
            (<strong>{{ $emailMasked }}</strong>)
        @endif
        . Vence en {{ $ttlMinutes ?? 10 }} minutos.
    </p>

    @if (session('status'))
        <div class="alert alert-success py-2 small" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('2fa.verify') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-shield-lock-fill"></span>
            </div>
            <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror"
                placeholder="Código de 6 dígitos" required autofocus>
            @error('code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="d-grid mb-2">
            <button type="submit" class="btn {{ config('adminlte.classes_auth_btn', 'btn-primary') }}">
                <i class="bi bi-check2-circle me-1"></i>
                Verificar código
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <form action="{{ route('2fa.resend') }}" method="post" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0">
                Reenviar código
            </button>
        </form>

        <form action="{{ route('2fa.cancel') }}" method="post" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-secondary p-0">
                Volver al login
            </button>
        </form>
    </div>
@stop
