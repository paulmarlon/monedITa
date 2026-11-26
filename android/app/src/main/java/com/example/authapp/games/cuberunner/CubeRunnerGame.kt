package com.example.authapp.games.cuberunner

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.DrawScope
import androidx.compose.ui.graphics.drawscope.rotate
import androidx.compose.ui.graphics.drawscope.scale
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.text.TextMeasurer
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.drawText
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.rememberTextMeasurer
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.authapp.core.game.GameModule
import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.games.common.GameFrameLoop
import com.example.authapp.games.common.GameOverlay
import com.example.authapp.games.common.GameTopBar
import com.example.authapp.games.common.HoldPad
import com.example.authapp.games.common.StartCountdown
import kotlinx.coroutines.delay
import kotlin.math.exp
import kotlin.math.max
import kotlin.math.min
import kotlin.math.roundToInt
import kotlin.random.Random

/*
 * ----------------------------- AJUSTES DE DIFICULTAD -----------------------------
 * Unidades de mundo: 600 x 220. Eje Y de pantalla hacia abajo; el suelo está en GROUND_Y.
 * Salto: mantener = más alto (gravedad 0.85x al subir); soltar = salto corto (gravedad 2x);
 * BAJAR / deslizar hacia abajo = caída rápida (gravedad 3.4x).
 */
private const val WORLD_W = 600f
private const val WORLD_H = 220f
private const val GROUND_Y = 180f
private const val PLAYER_X = 70f
private const val PLAYER_SIZE = 26f
private const val GRAVITY = 1800f
private const val JUMP_V = 540f
private const val MAX_FALL = 1500f
private const val JUMP_BUFFER = 0.12f      // si pulsas justo antes de aterrizar, el salto se ejecuta al tocar suelo
private const val SUBSTEP = 1f / 120f      // la simulación avanza en pasos fijos: sin atravesar obstáculos
private const val SPEED_START = 280f
private const val SPEED_GAIN = 340f        // velocidad máxima ≈ 620 u/s (asíntota: nunca injugable)
private const val SPEED_TAU = 45f          // segundos para alcanzar ~63% del incremento

private fun lerp(a: Float, b: Float, t: Float) = a + (b - a) * t

private class Spec(val dx: Float, val w: Float, val h: Float, val bottom: Float)
private class Group(var remaining: Int, val bonus: Int)
private class Obstacle(var x: Float, val w: Float, val h: Float, val bottom: Float, val group: Group)

private class PatternDef(
    val minDifficulty: Float,
    val weight: Float,
    val recover: Float,           // segundos de aire/recuperación que el patrón exige antes del siguiente
    val bonus: Int,
    val build: (speed: Float) -> List<Spec>
)

/** Patrones: simples, dobles, triples, aéreos y combinaciones de salto + caída rápida. */
private val PATTERNS = listOf(
    PatternDef(0.00f, 3.0f, 0.40f, 0) { listOf(Spec(0f, 16f, 28f, 0f)) },                         // cactus bajo
    PatternDef(0.00f, 2.5f, 0.45f, 0) { listOf(Spec(0f, 18f, 40f, 0f)) },                         // cactus alto
    PatternDef(0.08f, 2.5f, 0.50f, 3) { listOf(Spec(0f, 16f, 28f, 0f), Spec(30f, 16f, 28f, 0f)) },// doble
    PatternDef(0.10f, 2.0f, 0.25f, 3) { listOf(Spec(0f, 44f, 12f, 32f)) },                        // aéreo: NO saltar
    PatternDef(0.25f, 2.0f, 0.55f, 6) {                                                            // triple mixto
        listOf(Spec(0f, 16f, 28f, 0f), Spec(28f, 18f, 40f, 0f), Spec(60f, 16f, 28f, 0f))
    },
    PatternDef(0.30f, 1.6f, 0.45f, 6) { s ->                                                       // aéreo y luego cactus alto
        listOf(Spec(0f, 44f, 12f, 32f), Spec(s * 0.50f, 16f, 38f, 0f))
    },
    PatternDef(0.45f, 1.6f, 0.60f, 10) { s ->                                                      // salto corto + caída rápida
        listOf(Spec(0f, 16f, 28f, 0f), Spec(max(96f, s * 0.36f), 44f, 12f, 32f))
    },
    PatternDef(0.60f, 1.2f, 0.65f, 15) {                                                           // triple alto: salto máximo
        listOf(Spec(0f, 16f, 38f, 0f), Spec(34f, 16f, 38f, 0f), Spec(68f, 16f, 38f, 0f))
    }
)

private class RunnerWorld(seed: Int) {
    private val rng = Random(seed)
    val obstacles = ArrayList<Obstacle>()

    var t = 0f; private set
    var distance = 0f; private set
    var bonus = 0; private set
    var h = 0f; private set               // altura del cubo sobre el suelo
    var rot = 0f; private set
    var dead = false; private set
    var popupText: String? = null; private set
    var popupTtl = 0f; private set

    var jumpHeld = false
    var fastFall = false

    private var vy = 0f
    private var onGround = true
    private var jumpBuffer = 0f
    private var lastEndX = WORLD_W + 120f
    private var nextGap = 260f

    fun difficulty() = (t / 80f).coerceIn(0f, 1f)
    fun speed() = SPEED_START + SPEED_GAIN * (1f - exp(-t / SPEED_TAU))
    fun score() = (distance / 12f).toInt() + bonus

    fun startRun() {
        jumpHeld = false; fastFall = false; jumpBuffer = 0f
    }

    fun pressJump() {
        jumpHeld = true
        jumpBuffer = JUMP_BUFFER
    }

    fun releaseJump() {
        jumpHeld = false
    }

    /** Avanza [dt] segundos en pasos fijos (estable aunque un frame tarde). */
    fun advance(dt: Float) {
        var remaining = dt
        while (remaining > 0f && !dead) {
            val s = min(remaining, SUBSTEP)
            step(s)
            remaining -= s
        }
        if (popupTtl > 0f) popupTtl -= dt
    }

    private fun step(dt: Float) {
        t += dt
        val v = speed()
        distance += v * dt
        updatePlayer(dt)

        val dx = v * dt
        for (o in obstacles) o.x -= dx
        lastEndX -= dx
        spawn(v)

        if (hits()) {
            dead = true
            return
        }

        val iter = obstacles.iterator()
        while (iter.hasNext()) {
            val o = iter.next()
            if (o.x + o.w < PLAYER_X - 2f) {           // ya lo superaste
                val g = o.group
                g.remaining -= 1
                if (g.remaining == 0 && g.bonus > 0) {
                    bonus += g.bonus
                    popupText = "+${g.bonus} ¡Bien!"
                    popupTtl = 0.9f
                }
                iter.remove()
            }
        }
    }

    private fun updatePlayer(dt: Float) {
        if (jumpBuffer > 0f) jumpBuffer -= dt
        if (onGround && jumpBuffer > 0f) {
            vy = JUMP_V
            onGround = false
            jumpBuffer = 0f
        }
        if (!onGround) {
            val g = when {
                fastFall -> GRAVITY * 3.4f
                vy > 0f && jumpHeld -> GRAVITY * 0.85f   // mantener = salto alto
                vy > 0f -> GRAVITY * 2.0f                // soltar = salto corto
                else -> GRAVITY * 1.25f                  // caída más "pesada" que la subida
            }
            vy = max(vy - g * dt, -MAX_FALL)
            h += vy * dt
            rot += 540f * dt
            if (h <= 0f) {
                h = 0f
                vy = 0f
                onGround = true
                rot = ((rot / 90f).roundToInt() * 90f) % 360f
            }
        }
    }

    private fun spawn(v: Float) {
        while (lastEndX + nextGap < WORLD_W + 80f) {
            val def = pick()
            val specs = def.build(v)
            val x0 = lastEndX + nextGap
            val group = Group(specs.size, def.bonus)
            var endX = x0
            for (s in specs) {
                obstacles += Obstacle(x0 + s.dx, s.w, s.h, s.bottom, group)
                endX = max(endX, x0 + s.dx + s.w)
            }
            lastEndX = endX
            // Ventana de reacción: se acorta con la dificultad, pero siempre hay tiempo de aterrizar y reaccionar.
            val reaction = lerp(0.62f, 0.30f, difficulty()) + rng.nextFloat() * 0.22f
            nextGap = v * (def.recover + reaction)
        }
    }

    private fun pick(): PatternDef {
        val d = difficulty()
        val pool = PATTERNS.filter { d >= it.minDifficulty }
        val weights = pool.map { it.weight * (1f + it.minDifficulty * d * 2f) } // los difíciles ganan peso con el tiempo
        var r = rng.nextFloat() * weights.sum()
        for (i in pool.indices) {
            r -= weights[i]
            if (r <= 0f) return pool[i]
        }
        return pool.last()
    }

    /** Hitboxes ajustadas (4 u por lado en X, 2-3 u en Y) para evitar colisiones "fantasma". */
    private fun hits(): Boolean {
        val pl = PLAYER_X + 4f
        val pr = PLAYER_X + PLAYER_SIZE - 4f
        val pt = GROUND_Y - h - PLAYER_SIZE + 3f
        val pb = GROUND_Y - h - 2f
        for (o in obstacles) {
            val ol = o.x + 2f
            val or = o.x + o.w - 2f
            if (or <= pl || ol >= pr) continue
            val ot = GROUND_Y - o.bottom - o.h + 2f
            val ob = GROUND_Y - o.bottom - 2f
            if (pb > ot && pt < ob) return true
        }
        return false
    }
}

private enum class RunnerPhase { READY, COUNTDOWN, RUNNING, DEAD, ENDED }

private class Palette(
    val bg: Color, val ground: Color, val player: Color,
    val obstacle: Color, val aerial: Color, val text: Color
)

object CubeRunnerGame : GameModule {
    override val slug = "cube_runner"
    override val title = "Cube Runner Eco."
    override val description =
        "El clásico juego del dinosaurio adaptado para la plataforma. Salta los obstáculos y acumula la mayor cantidad de puntos."

    @Composable
    override fun Content(
        session: GameSession,
        onFinished: (GameResult) -> Unit,
        onExit: () -> Unit
    ) {
        // Semilla por partida: cada partida genera un recorrido distinto
        val world = remember(session.sessionId) { RunnerWorld(session.sessionId.hashCode()) }
        var phase by remember { mutableStateOf(RunnerPhase.READY) }
        var frame by remember { mutableLongStateOf(0L) }           // fuerza el redibujo del Canvas
        var scoreShown by remember { mutableIntStateOf(0) }        // solo cambia cuando cambia el puntaje
        val currentOnFinished by rememberUpdatedState(onFinished)

        GameFrameLoop(running = phase == RunnerPhase.RUNNING) { dt ->
            world.advance(dt)
            frame += 1
            val sc = world.score()
            if (sc != scoreShown) scoreShown = sc
            if (world.dead) phase = RunnerPhase.DEAD
        }

        LaunchedEffect(phase) {
            if (phase == RunnerPhase.DEAD || phase == RunnerPhase.ENDED) {
                delay(if (phase == RunnerPhase.ENDED) 300 else 900)
                currentOnFinished(GameResult(score = world.score(), durationMs = (world.t * 1000f).toLong()))
            }
        }

        val cs = MaterialTheme.colorScheme
        val palette = Palette(cs.surfaceVariant, cs.onSurfaceVariant, cs.primary, cs.error, cs.tertiary, cs.primary)
        val measurer = rememberTextMeasurer()

        Column(
            modifier = Modifier.fillMaxSize().padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            GameTopBar(
                "Cube Runner Eco.", "Puntos: $scoreShown", onExit,
                onFinish = if (phase == RunnerPhase.RUNNING) ({ phase = RunnerPhase.ENDED }) else null
            )

            Box(Modifier.fillMaxWidth()) {
                Canvas(
                    Modifier
                        .fillMaxWidth()
                        .aspectRatio(WORLD_W / WORLD_H)
                        .clip(RoundedCornerShape(12.dp))
                        .pointerInput(world) {
                            // Tocar el área de juego = saltar (mantener = más alto). Deslizar hacia abajo = caída rápida.
                            awaitEachGesture {
                                val down = awaitFirstDown(requireUnconsumed = false)
                                world.pressJump()
                                val threshold = 28.dp.toPx()
                                var fast = false
                                while (true) {
                                    val event = awaitPointerEvent()
                                    val c = event.changes.firstOrNull { it.id == down.id }
                                    if (c == null || !c.pressed) break
                                    if (!fast && c.position.y - down.position.y > threshold) {
                                        fast = true
                                        world.fastFall = true
                                    }
                                }
                                world.releaseJump()
                                if (fast) world.fastFall = false
                            }
                        }
                ) {
                    @Suppress("UNUSED_VARIABLE") val tick = frame     // suscribe el dibujo al frame actual
                    drawRunner(world, palette, measurer)
                }

                when (phase) {
                    RunnerPhase.READY -> GameOverlay(Modifier.matchParentSize()) {
                        Text("Cube Runner Eco.", style = MaterialTheme.typography.titleLarge, color = Color.White)
                        Text(
                            "SALTAR: mantén para saltar más alto.\nBAJAR (o desliza abajo): caída rápida.\n«Terminar» guarda tu puntaje; «Salir» no.",
                            color = Color.White, style = MaterialTheme.typography.bodySmall
                        )
                        Button(onClick = { phase = RunnerPhase.COUNTDOWN }) { Text("Empezar") }
                    }
                    RunnerPhase.COUNTDOWN -> GameOverlay(Modifier.matchParentSize()) {
                        StartCountdown {
                            world.startRun()
                            phase = RunnerPhase.RUNNING
                        }
                    }
                    RunnerPhase.DEAD -> GameOverlay(Modifier.matchParentSize()) {
                        Text("¡Chocaste!", style = MaterialTheme.typography.headlineMedium, color = Color.White)
                        Text("Puntos: $scoreShown", color = Color.White)
                    }
                    RunnerPhase.ENDED -> GameOverlay(Modifier.matchParentSize()) {
                        Text("Partida terminada", style = MaterialTheme.typography.headlineMedium, color = Color.White)
                        Text("Puntos: $scoreShown", color = Color.White)
                    }
                    RunnerPhase.RUNNING -> Unit
                }
            }

            Row(
                Modifier.fillMaxWidth().weight(1f),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                HoldPad("SALTAR", onDown = world::pressJump, onUp = world::releaseJump,
                    modifier = Modifier.weight(2f).fillMaxHeight())
                HoldPad("BAJAR", onDown = { world.fastFall = true }, onUp = { world.fastFall = false },
                    modifier = Modifier.weight(1f).fillMaxHeight())
            }
        }
    }
}

private fun DrawScope.drawRunner(world: RunnerWorld, pal: Palette, measurer: TextMeasurer) {
    val s = size.width / WORLD_W
    scale(scale = s, pivot = Offset.Zero) {
        drawRect(pal.bg, Offset.Zero, Size(WORLD_W, WORLD_H))
        drawRect(pal.ground, Offset(0f, GROUND_Y), Size(WORLD_W, 2f))

        var x = -(world.distance % 48f)                       // marcas del suelo en movimiento
        while (x < WORLD_W) {
            drawRect(pal.ground.copy(alpha = 0.45f), Offset(x, GROUND_Y + 10f), Size(16f, 2f))
            x += 48f
        }

        for (o in world.obstacles) {
            val top = GROUND_Y - o.bottom - o.h
            drawRect(if (o.bottom > 0f) pal.aerial else pal.obstacle, Offset(o.x, top), Size(o.w, o.h))
        }

        val py = GROUND_Y - world.h - PLAYER_SIZE
        rotate(degrees = world.rot, pivot = Offset(PLAYER_X + PLAYER_SIZE / 2f, py + PLAYER_SIZE / 2f)) {
            drawRect(pal.player, Offset(PLAYER_X, py), Size(PLAYER_SIZE, PLAYER_SIZE))
        }
    }
    if (world.popupTtl > 0f) {
        world.popupText?.let {
            drawText(
                measurer, it, Offset(size.width * 0.55f, size.height * 0.10f),
                TextStyle(color = pal.text, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            )
        }
    }
}
