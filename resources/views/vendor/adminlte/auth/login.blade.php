@php
    // Si ya tenemos el logo globalmente y es una URL completa
    if (isset($configGlobal) && !empty($configGlobal->logo)) {
        config(['adminlte.logo_img' => $configGlobal->logo]);
    }
@endphp

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ $configGlobal->logo ?? asset('usb/bosco.png') }}" type="image/png">
@stop

@extends('adminlte::auth.auth-page', ['authType' => 'login'])
...

@section('title', $configGlobal->nombre ?? '@tech')

{{-- Esto añade la imagen del logo como favicon de la pestaña --}}
@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('storage/' . ($configLogo->logo ?? 'usb/bosco.png')) }}" type="image/png">
@stop

@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $loginUrl = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $registerUrl = View::getSection('register_url') ?? config('adminlte.register_url', 'register');
    $passResetUrl = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');

    $loginUrl = $layoutHelper->makeUrl($loginUrl);
    $registerUrl = $layoutHelper->makeUrl($registerUrl);
    $passResetUrl = $layoutHelper->makeUrl($passResetUrl);
@endphp

@section('auth_header', __('adminlte::adminlte.login_message'))

@section('auth_body')
    <form action="{{ $loginUrl }}" method="post">
        @csrf

        {{-- Registro Universitario field --}}
        <label for="registro_universitario" class="visually-hidden">Registro Universitario</label>

        <div class="input-group mb-3">
            <div class="input-group-text">
                <span class="bi bi-person-badge {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>
            <input type="text" name="registro_universitario" id="registro_universitario"
                class="form-control @error('registro_universitario') is-invalid @enderror"
                value="{{ old('registro_universitario') }}" placeholder="Registro Universitario" required autofocus>
            @error('registro_universitario')
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

        {{-- Login field --}}
        <div class="row">
            <div class="col-7">
                <div class="form-check" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        {{ __('adminlte::adminlte.remember_me') }}
                    </label>
                </div>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn {{ config('adminlte.classes_auth_btn', 'btn-primary') }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        {{ __('adminlte::adminlte.sign_in') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
    @include('adminlte::auth.social-links', ['fallbackText' => __('adminlte::adminlte.sign_in')])
@stop

@section('auth_footer')
    {{-- Password reset link --}}
    @if ($passResetUrl)
        <p class="my-0">
            <a href="{{ $passResetUrl }}">
                {{ __('adminlte::adminlte.i_forgot_my_password') }}
            </a>
        </p>
    @endif

    {{-- Register link --}}
    @if ($registerUrl)
        <p class="my-0">
            <a href="{{ $registerUrl }}">
                {{ __('adminlte::adminlte.register_a_new_membership') }}
            </a>
        </p>
    @endif
@stop
