@extends('adminlte::page')

@section('title', 'Dino Runner')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Minijuego: <b>Dino Runner (Chrome Style)</b> 🦖</h1>
        <a href="{{ route('minijuegos-puntajes.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-trophy me-1"></i> Ver Ranking Global (Top 5)
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card card-dark shadow">
                    <div class="card-header">
                        <h3 class="card-title float-none">Presiona ESPACIO o FLECHA ARRIBA para saltar</h3>
                    </div>
                    <div class="card-body bg-black p-4 rounded d-flex flex-column align-items-center">
                        <!-- Puntaje en vivo -->
                        <div class="text-white mb-2">
                            Puntaje actual: <span id="score-val" class="fw-bold text-success fs-4">0</span>
                        </div>

                        <!-- Canvas del juego real -->
                        <div id="game-container"
                            style="position: relative; border-radius: 8px; overflow: hidden; background: #1e1e2f;">
                            <canvas id="dinoCanvas" width="600" height="200"
                                style="display: block; background: #1e1e2f;"></canvas>

                            <!-- Pantalla de inicio superpuesta -->
                            <div id="dino-screen"
                                class="position-absolute top-0 left-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark bg-opacity-75 text-white">
                                <h4 class="fw-bold">T-Rex Runner</h4>
                                <p class="text-light mb-3">Haz clic para comenzar a jugar</p>
                                <button id="start-btn" class="btn btn-primary shadow">¡Iniciar Partida!</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Al terminar la partida, tu puntaje se registrará automáticamente en el sistema.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const canvas = document.getElementById('dinoCanvas');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const startBtn = document.getElementById('start-btn');
            const dinoScreen = document.getElementById('dino-screen');
            const scoreVal = document.getElementById('score-val');

            let score = 0;
            let gameRunning = false;
            let animationId = null;

            // Dinosaurio (Ajustado para menor permanencia en el aire / caída más rápida)
            let dino = {
                x: 50,
                y: 125,
                width: 32,
                height: 42,
                vy: 0,
                gravity: 0.95, // <--- Aumentado ligeramente para caer más rápido
                jumpPower: -13.0, // <--- Salto más reactivo
                isJumping: false
            };

            let obstacles = [];
            let obstacleTimer = 0;
            let nextObstacleDistance = 60; // <--- Espaciado inicial más cercano
            let gameSpeed = 5;

            // Nubes en el cielo de fondo
            let clouds = [{
                    x: 100,
                    y: 40
                },
                {
                    x: 350,
                    y: 70
                },
                {
                    x: 550,
                    y: 30
                }
            ];

            // Controles
            document.addEventListener('keydown', function(e) {
                if ((e.code === 'Space' || e.code === 'ArrowUp') && gameRunning) {
                    e.preventDefault();
                    jump();
                }
            });

            canvas.addEventListener('click', function() {
                if (gameRunning) {
                    jump();
                }
            });

            function jump() {
                if (!dino.isJumping) {
                    dino.vy = dino.jumpPower;
                    dino.isJumping = true;
                }
            }

            // Iniciar partida validando la ficha
            startBtn.addEventListener('click', function() {
                startBtn.disabled = true;
                startBtn.innerText = "Verificando fichas...";

                fetch("{{ route('minijuegos.iniciar', 'dinosaurio_runner') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            startGame();
                        } else {
                            alert(data.message);
                            startBtn.disabled = false;
                            startBtn.innerText = "¡Iniciar Partida!";
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ocurrió un error al intentar iniciar la partida.');
                        startBtn.disabled = false;
                        startBtn.innerText = "¡Iniciar Partida!";
                    });
            });

            function startGame() {
                dinoScreen.classList.add('d-none');
                dinoScreen.style.display = 'none';

                score = 0;
                obstacles = [];
                obstacleTimer = 0;
                gameSpeed = 5.5; // <--- Velocidad inicial un poco más fluida
                nextObstacleDistance = 60;
                dino.y = 125;
                dino.vy = 0;
                dino.isJumping = false;
                gameRunning = true;
                loop();
            }

            function update() {
                score += 1;
                scoreVal.innerText = Math.floor(score / 5);

                // Incremento dinámico de velocidad controlado
                gameSpeed = 5.5 + Math.floor(score / 300) * 0.5;

                // Física del salto (mayor gravedad = menos tiempo flotando)
                dino.vy += dino.gravity;
                dino.y += dino.vy;

                // Suelo
                if (dino.y > 125) {
                    dino.y = 125;
                    dino.vy = 0;
                    dino.isJumping = false;
                }

                // Movimiento de nubes
                clouds.forEach(cloud => {
                    cloud.x -= gameSpeed * 0.25;
                    if (cloud.x < -40) cloud.x = canvas.width + 40;
                });

                // Generación de obstáculos con ESPACIADO VARIABLE Y CERCANO
                obstacleTimer++;
                if (obstacleTimer >= nextObstacleDistance) {
                    let tipos = [{
                            width: 16,
                            height: 30,
                            y: 138
                        },
                        {
                            width: 22,
                            height: 42,
                            y: 126
                        },
                        {
                            width: 30,
                            height: 24,
                            y: 144
                        }
                    ];
                    let tipoSeleccionado = tipos[Math.floor(Math.random() * tipos.length)];

                    obstacles.push({
                        x: canvas.width,
                        y: tipoSeleccionado.y,
                        width: tipoSeleccionado.width,
                        height: tipoSeleccionado.height
                    });

                    obstacleTimer = 0;

                    // Espaciado variable más dinámico y cercano (mínimo 45px, máximo 95px)
                    let factorDificultad = Math.min(Math.floor(score / 300), 3);
                    let minSpacing = Math.max(40, 55 - (factorDificultad * 4));
                    let maxSpacing = Math.max(70, 95 - (factorDificultad * 6));

                    nextObstacleDistance = Math.floor(Math.random() * (maxSpacing - minSpacing + 1)) + minSpacing;
                }

                // Movimiento y colisiones
                for (let i = obstacles.length - 1; i >= 0; i--) {
                    obstacles[i].x -= gameSpeed;

                    if (
                        dino.x < obstacles[i].x + obstacles[i].width &&
                        dino.x + dino.width > obstacles[i].x &&
                        dino.y < obstacles[i].y + obstacles[i].height &&
                        dino.y + dino.height > obstacles[i].y
                    ) {
                        gameOver();
                        return;
                    }

                    if (obstacles[i].x + obstacles[i].width < 0) {
                        obstacles.splice(i, 1);
                    }
                }
            }

            function draw() {
                // Fondo Oscuro Estilo Arcade
                ctx.fillStyle = '#1e1e2f';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.fillStyle = '#2c2c44';
                clouds.forEach(cloud => {
                    ctx.fillRect(cloud.x, cloud.y, 35, 10);
                    ctx.fillRect(cloud.x + 8, cloud.y - 5, 18, 8);
                });

                ctx.strokeStyle = '#00d2ff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(0, 168);
                ctx.lineTo(canvas.width, 168);
                ctx.stroke();

                ctx.fillStyle = '#4a4a75';
                ctx.fillRect(150, 172, 4, 2);
                ctx.fillRect(380, 175, 6, 2);

                // Dinosaurio verde neón
                ctx.fillStyle = '#2ecc71';
                ctx.fillRect(dino.x, dino.y, dino.width, dino.height);
                ctx.fillStyle = '#1e1e2f';
                ctx.fillRect(dino.x + 20, dino.y + 8, 4, 4);

                // Obstáculos
                ctx.fillStyle = '#e74c3c';
                obstacles.forEach(obs => {
                    ctx.fillRect(obs.x, obs.y, obs.width, obs.height);
                });
            }

            function loop() {
                if (!gameRunning) return;
                update();
                draw();
                animationId = requestAnimationFrame(loop);
            }

            function gameOver() {
                gameRunning = false;
                cancelAnimationFrame(animationId);

                let finalScore = Math.floor(score / 5);

                fetch("{{ route('minijuegos.guardar') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            puntaje: finalScore
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        dinoScreen.style.setProperty('display', 'flex', 'important');
                        dinoScreen.classList.remove('d-none');

                        if (data.success) {
                            dinoScreen.querySelector('h4').innerText = "¡Game Over!";
                            dinoScreen.querySelector('p').innerHTML =
                                "Puntaje guardado con éxito: <b class='text-success'>" + finalScore +
                                " pts</b>";
                        } else {
                            dinoScreen.querySelector('h4').innerText = "¡Game Over!";
                            dinoScreen.querySelector('p').innerText = "Puntaje final: " + finalScore + " pts";
                        }

                        startBtn.disabled = false;
                        startBtn.innerText = "¡Volver a Jugar (1 Ficha)!";
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        dinoScreen.style.setProperty('display', 'flex', 'important');
                        dinoScreen.classList.remove('d-none');
                        dinoScreen.querySelector('h4').innerText = "¡Game Over!";
                        dinoScreen.querySelector('p').innerText = "Puntaje final: " + finalScore + " pts";

                        startBtn.disabled = false;
                        startBtn.innerText = "Reintentar";
                    });
            }
        });
    </script>
@endsection
