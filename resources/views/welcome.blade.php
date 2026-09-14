<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Título dinámico conectado a la Base de Datos --}}
    <title>{{ $configGlobal->nombre ?? 'tapITa' }}</title>

    {{-- Favicon dinámico --}}
    <link rel="icon" href="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" type="image/png">

    {{-- Google Fonts & Bootstrap 5 CSS --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hero-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

    {{-- Navbar Superior Simple --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}" alt="Logo"
                    width="30" height="30" class="rounded-circle object-fit-cover">
                <span class="fw-bold">{!! $configGlobal->nombre ?? 'tap<b>IT</b>a' !!}</span>
            </a>
            <div>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="btn btn-outline-light btn-sm px-3">Panel de Control</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-success btn-sm px-3 me-2">Iniciar Sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm px-3">Registrarse</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    {{-- Contenido Central Principal --}}
    <main class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">

                {{-- Logo grande centrado --}}
                <div class="mb-4">
                    <img src="{{ asset('storage/' . ($configGlobal->logo ?? 'usb/don bosco.png')) }}"
                        alt="Logotipo Institucional" class="rounded-circle shadow border-3 border-white p-1 bg-white"
                        style="width: 110px; height: 110px; object-fit: cover;">
                </div>

                <div class="card hero-card p-4 p-md-5 bg-white">
                    <h1 class="display-6 fw-bold text-dark mb-3">
                        Bienvenido al sistema <span class="text-success">{!! $configGlobal->nombre ?? 'tapITa' !!}</span>
                    </h1>

                    <p class="text-muted lead mb-4 fs-6">
                        {{ $configGlobal->descripcion ?? 'Plataforma oficial de gestión, reciclaje y control institucional.' }}
                    </p>

                    <div class="d-flex justify-content-center gap-2">
                        @auth
                            <a href="{{ url('/home') }}" class="btn btn-success btn-lg px-4 shadow-sm">
                                <i class="bi bi-speedometer2 me-2"></i> Ir al Sistema
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-success btn-lg px-4 shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Acceder a la Plataforma
                            </a>
                        @endauth
                    </div>
                </div>

            </div>
        </div>
    </main>

    {{-- Footer sencillo --}}
    <footer class="text-center py-3 text-muted small bg-white border-top">
        <div class="container">
            &copy; {{ date('Y') }} <b>{{ $configGlobal->nombre ?? 'tapITa' }}</b> — Todos los derechos reservados.
        </div>
    </footer>

</body>

</html>
