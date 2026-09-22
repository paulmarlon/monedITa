@php
    // Si ya tenemos el logo globalmente, actualizamos la configuración de AdminLTE de inmediato
    if (isset($configGlobal) && !empty($configGlobal->logo)) {
        config(['adminlte.logo_img' => 'storage/' . $configGlobal->logo]);
    }
@endphp

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/bosco.png')) }}" type="image/png">
@stop

@extends('adminlte::auth.auth-page', ['authType' => 'register'])

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $loginUrl = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $registerUrl = View::getSection('register_url') ?? config('adminlte.register_url', 'register');

    $loginUrl = $layoutHelper->makeUrl($loginUrl);
    $registerUrl = $layoutHelper->makeUrl($registerUrl);
@endphp

@section('auth_header', __('adminlte::adminlte.register_message'))

@section('auth_body')
    <form action="{{ $registerUrl }}" method="post">
        @csrf

        {{-- Registro Universitario field --}}
        <label for="registro_universitario" class="visually-hidden">Registro Universitario</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-person-badge {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="text" name="registro_universitario" id="registro_universitario"
                class="form-control @error('registro_universitario') is-invalid @enderror"
                value="{{ old('registro_universitario') }}" placeholder="Registro Universitario (solo numeros)" required
                autofocus>

            @error('registro_universitario')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Name field --}}
        <label for="name" class="visually-hidden">{{ __('adminlte::adminlte.full_name') }}</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-person-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" placeholder="{{ __('adminlte::adminlte.full_name') }}" required>

            @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Alias field (Obligatorio y Único en la plataforma) --}}
        <label for="alias" class="visually-hidden">Alias (Apodo)</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-controller {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="text" name="alias" id="alias" class="form-control @error('alias') is-invalid @enderror"
                value="{{ old('alias') }}" placeholder="Alias o Apodo en la plataforma" required>

            @error('alias')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Email field (Obligatorio y con dominio institucional) --}}
        <label for="email" class="visually-hidden">Correo Institucional (@usalesiana.edu.bo)</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-envelope-at {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="usuario@usalesiana.edu.bo" required>

            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Password field --}}
        <label for="password" class="visually-hidden">{{ __('adminlte::adminlte.password') }}</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-lock-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="{{ __('adminlte::adminlte.password') }}" required>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Confirm password field --}}
        <label for="password_confirmation" class="visually-hidden">
            {{ __('adminlte::adminlte.retype_password') }}
        </label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-lock-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            <input type="password" name="password_confirmation" id="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                placeholder="{{ __('adminlte::adminlte.retype_password') }}" required>

            @error('password_confirmation')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Register button --}}
        <div class="d-grid">
            <button type="submit" class="btn {{ config('adminlte.classes_auth_btn', 'btn-primary') }}">
                <i class="bi bi-person-plus me-1"></i>
                {{ __('adminlte::adminlte.register') }}
            </button>
        </div>
    </form>
    @include('adminlte::auth.social-links', ['fallbackText' => __('adminlte::adminlte.register')])
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ $loginUrl }}">
            {{ __('adminlte::adminlte.i_already_have_a_membership') }}
        </a>
    </p>
@stop
