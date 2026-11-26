package com.example.authapp.games.sapito

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.gestures.detectDragGestures
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.DrawScope
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
import kotlin.math.PI
import kotlin.math.abs
import kotlin.math.ceil
import kotlin.math.exp
import kotlin.math.floor
import kotlin.math.max
import kotlin.math.min
import kotlin.math.sin
import kotlin.random.Random

/*
 * Unidades = CELDAS. 9 columnas x 12 filas visibles. La cámara sigue al sapito hacia adelante.
 * Cada 5 filas hay una "isla" segura (ola superada). Fila TARGET_ROW = meta (tramo completo).
 */
private const val COLS = 9
private const val ROWS = 12
private const val TARGET_ROW = 40
private const val MARGIN = 2.5f                 // los vehículos salen/entran fuera de pantalla
private const val PERIOD = COLS + 2 * MARGIN
private const val HOP_TIME = 0.12f
private const val SUBSTEP = 1f / 120f

private fun lerp(a: Float, b: Float, t: Float) = a + (b - a) * t

private enum class Dir { UP, DOWN, LEFT, RIGHT }
private enum class LaneKind { SAFE, ROAD }
private enum class SapitoPhase { READY, COUNTDOWN, RUNNING, DEAD, WON, ENDED }

private class Lane(
    val kind: LaneKind, val dir: Int, val speed: Float, val len: Float,
    val count: Int, val phase: Float, val color: Color
)

private val CAR = Color(0xFFEF5350)
private val TRUCK = Color(0xFFFFB300)
private val MOTO = Color(0xFF42A5F5)

private class FrogWorld(private val seed: Int) {
    private val lanes = HashMap<Int, Lane>()

    var t = 0f; private set
    var col = COLS / 2; private set
    var row = 0; private set
    var fx = col + 0.5f; private set          // posición visual (interpolada durante el salto)
    var fy = 0.5f; private set
    var hopT = 1f; private set
    var maxRow = 0; private set
    var bonus = 0; private set
    var camY = 0f; private set
    var dead = false; private set
    var won = false; private set
    var popupText: String? = null; private set
    var popupTtl = 0f; private set

    private var fromX = fx
    private var fromY = fy
    private var queued: Dir? = null
    private var rewardedMedian = 0

    fun score() = maxRow * 10 + bonus
    fun wave() = min(maxRow / 5 + 1, TARGET_ROW / 5)

    fun laneAt(r: Int): Lane = lanes.getOrPut(r) { makeLane(r) }

    private fun makeLane(r: Int): Lane {
        if (r <= 0 || r % 5 == 0 || r >= TARGET_ROW) {
            return Lane(LaneKind.SAFE, 0, 0f, 0f, 0, 0f, Color.Transparent)
        }
        val rnd = Random(seed * 31 + r)                 // determinista por fila y partida
        val d = (r / TARGET_ROW.toFloat()).coerceIn(0f, 1f)
        val dir = if (rnd.nextBoolean()) 1 else -1
        val pick = rnd.nextFloat()
        val (len, mul, color) = when {
            pick < 0.5f -> Triple(1.0f, 1.0f, CAR)
            pick < 0.8f -> Triple(2.0f, 0.8f, TRUCK)
            else -> Triple(0.7f, 1.35f, MOTO)
        }
        val speed = (lerp(1.5f, 3.4f, d) + rnd.nextFloat() * 0.8f) * mul   // más rápido a mayor fila
        val gap = lerp(3.4f, 1.9f, d) + rnd.nextFloat() * 0.7f              // huecos más chicos a mayor fila
        val count = floor(PERIOD / (len + gap)).toInt().coerceIn(1, 5)
        return Lane(LaneKind.ROAD, dir, speed, len, count, rnd.nextFloat() * PERIOD, color)
    }

    /** Posición X (izquierda) del vehículo i en el instante t; analítica => sin deriva ni acumulación de errores. */
    fun vehicleX(l: Lane, i: Int, time: Float): Float {
        val spacing = PERIOD / l.count
        val raw = l.phase + i * spacing + l.dir * l.speed * time
        val m = ((raw % PERIOD) + PERIOD) % PERIOD
        return m - MARGIN
    }

    fun hop(dir: Dir) {
        if (dead || won) return
        if (hopT < 1f) {                        // un salto en cola para que el control se sienta ágil
            queued = dir
            return
        }
        var nc = col
        var nr = row
        when (dir) {
            Dir.UP -> nr += 1
            Dir.DOWN -> nr -= 1
            Dir.LEFT -> nc -= 1
            Dir.RIGHT -> nc += 1
        }
        if (nc !in 0 until COLS || nr < 0) return
        if (nr < ceil(camY - 0.01f).toInt()) return   // no puede retroceder fuera de la pantalla
        fromX = fx
        fromY = fy
        col = nc
        row = nr
        hopT = 0f
    }

    fun advance(dt: Float) {
        var remaining = dt
        while (remaining > 0f && !dead && !won) {
            val s = min(remaining, SUBSTEP)
            step(s)
            remaining -= s
        }
        if (popupTtl > 0f) popupTtl -= dt
    }

    private fun step(dt: Float) {
        t += dt
        if (hopT < 1f) {
            hopT = min(1f, hopT + dt / HOP_TIME)
            fx = fromX + (col + 0.5f - fromX) * hopT
            fy = fromY + (row + 0.5f - fromY) * hopT
            if (hopT >= 1f) onLanded()
        }
        val target = max(0, maxRow - 3).toFloat()
        if (target > camY) camY += (target - camY) * (1f - exp(-10f * dt))
        if (collides()) dead = true
    }

    private fun onLanded() {
        if (row > maxRow) {
            maxRow = row
            if (row % 5 == 0 && row > rewardedMedian && row < TARGET_ROW) {
                rewardedMedian = row
                bonus += 40
                popupText = "+40 ¡Ola superada!"
                popupTtl = 1.0f
            }
            if (row >= TARGET_ROW) {
                won = true
                bonus += 300
                popupText = "+300 ¡Cruzaste!"
                popupTtl = 1.5f
            }
        }
        queued?.let {
            queued = null
            hop(it)
        }
    }

    /** Colisión AABB: sapito (0.54 de celda) contra cada vehículo de las filas que toca, con tolerancia de 0.04. */
    private fun collides(): Boolean {
        val half = 0.27f
        val fl = fx - half
        val fr = fx + half
        val fb = fy - half
        val ft = fy + half
        val r0 = floor(fb).toInt()
        val r1 = floor(ft).toInt()
        for (r in r0..r1) {
            val lane = laneAt(r)
            if (lane.kind != LaneKind.ROAD) continue
            val vb = r + 0.18f
            val vt = r + 0.82f
            if (ft <= vb || fb >= vt) continue
            for (i in 0 until lane.count) {
                val vx = vehicleX(lane, i, t)
                if (fr > vx + 0.04f && fl < vx + lane.len - 0.04f) return true
            }
        }
        return false
    }
}

object SapitoGame : GameModule {
    override val slug = "sapito-avenida"
    override val title = "Sapito sobre avenida"
    override val description =
        "Cruza la avenida esquivando los vehículos. Cada carril cuenta y cada ola se pone más rápida."

    @Composable
    override fun Content(
        session: GameSession,
        onFinished: (GameResult) -> Unit,
        onExit: () -> Unit
    ) {
        val world = remember(session.sessionId) { FrogWorld(session.sessionId.hashCode()) }
        var phase by remember { mutableStateOf(SapitoPhase.READY) }
        var frame by remember { mutableLongStateOf(0L) }
        var scoreShown by remember { mutableIntStateOf(0) }
        val currentOnFinished by rememberUpdatedState(onFinished)
        val phaseNow by rememberUpdatedState(phase)
        val measurer = rememberTextMeasurer()

        GameFrameLoop(running = phase == SapitoPhase.RUNNING) { dt ->
            world.advance(dt)
            frame += 1
            val sc = world.score()
            if (sc != scoreShown) scoreShown = sc
            if (world.dead) phase = SapitoPhase.DEAD else if (world.won) phase = SapitoPhase.WON
        }

        LaunchedEffect(phase) {
            if (phase == SapitoPhase.DEAD || phase == SapitoPhase.WON || phase == SapitoPhase.ENDED) {
                delay(
                    when (phase) {
                        SapitoPhase.WON -> 1200L
                        SapitoPhase.ENDED -> 300L
                        else -> 900L
                    }
                )
                currentOnFinished(GameResult(score = world.score(), durationMs = (world.t * 1000f).toLong()))
            }
        }

        fun move(d: Dir) {
            if (phaseNow == SapitoPhase.RUNNING) world.hop(d)
        }

        Column(
            modifier = Modifier.fillMaxSize().padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            GameTopBar(
                "Sapito", "${scoreShown} pts · Ola ${world.wave()}", onExit,
                onFinish = if (phase == SapitoPhase.RUNNING) ({ phase = SapitoPhase.ENDED }) else null
            )

            BoxWithConstraints(Modifier.fillMaxWidth().weight(1f), contentAlignment = Alignment.Center) {
                val cellDp = minOf(maxWidth / COLS, maxHeight / ROWS)
                Box(Modifier.size(cellDp * COLS, cellDp * ROWS)) {
                    Canvas(
                        Modifier
                            .fillMaxSize()
                            .clip(RoundedCornerShape(12.dp))
                            .pointerInput(world) { detectTapGestures(onTap = { move(Dir.UP) }) }
                            .pointerInput(world) {
                                var dx = 0f
                                var dy = 0f
                                detectDragGestures(
                                    onDragStart = { dx = 0f; dy = 0f },
                                    onDrag = { change, amount ->
                                        change.consume()
                                        dx += amount.x
                                        dy += amount.y
                                    },
                                    onDragEnd = {
                                        val th = 24.dp.toPx()
                                        if (abs(dx) > th || abs(dy) > th) {
                                            move(
                                                if (abs(dx) > abs(dy)) (if (dx > 0f) Dir.RIGHT else Dir.LEFT)
                                                else (if (dy > 0f) Dir.DOWN else Dir.UP)
                                            )
                                        }
                                    }
                                )
                            }
                    ) {
                        @Suppress("UNUSED_VARIABLE") val tick = frame
                        drawSapito(world, measurer)
                    }

                    when (phase) {
                        SapitoPhase.READY -> GameOverlay(Modifier.matchParentSize()) {
                            Text("Sapito sobre avenida", style = MaterialTheme.typography.titleLarge, color = Color.White)
                            Text(
                                "Toca o desliza ↑ para avanzar.\nDesliza o usa los botones para moverte.\nLlega a la fila $TARGET_ROW.\n«Terminar» guarda tu puntaje; «Salir» no.",
                                color = Color.White, style = MaterialTheme.typography.bodySmall
                            )
                            Button(onClick = { phase = SapitoPhase.COUNTDOWN }) { Text("Empezar") }
                        }
                        SapitoPhase.COUNTDOWN -> GameOverlay(Modifier.matchParentSize()) {
                            StartCountdown { phase = SapitoPhase.RUNNING }
                        }
                        SapitoPhase.DEAD -> GameOverlay(Modifier.matchParentSize()) {
                            Text("¡Atropellado!", style = MaterialTheme.typography.headlineMedium, color = Color.White)
                            Text("Puntos: $scoreShown", color = Color.White)
                        }
                        SapitoPhase.WON -> GameOverlay(Modifier.matchParentSize()) {
                            Text("¡Cruzaste la avenida!", style = MaterialTheme.typography.headlineMedium, color = Color.White)
                            Text("Puntos: $scoreShown", color = Color.White)
                        }
                        SapitoPhase.ENDED -> GameOverlay(Modifier.matchParentSize()) {
                            Text("Partida terminada", style = MaterialTheme.typography.headlineMedium, color = Color.White)
                            Text("Puntos: $scoreShown", color = Color.White)
                        }
                        SapitoPhase.RUNNING -> Unit
                    }
                }
            }

            Column(horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(8.dp)) {
                HoldPad("▲", onDown = { move(Dir.UP) }, onUp = {}, modifier = Modifier.size(120.dp, 52.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    HoldPad("◀", onDown = { move(Dir.LEFT) }, onUp = {}, modifier = Modifier.size(80.dp, 52.dp))
                    HoldPad("▼", onDown = { move(Dir.DOWN) }, onUp = {}, modifier = Modifier.size(80.dp, 52.dp))
                    HoldPad("▶", onDown = { move(Dir.RIGHT) }, onUp = {}, modifier = Modifier.size(80.dp, 52.dp))
                }
            }
        }
    }
}

private val GRASS = Color(0xFF8BC34A)
private val ROAD = Color(0xFF455A64)
private val GOAL = Color(0xFFFFD54F)
private val FROG = Color(0xFF2E7D32)
private val FROG_DEAD = Color(0xFFB71C1C)

private fun DrawScope.drawSapito(w: FrogWorld, measurer: TextMeasurer) {
    val cell = size.width / COLS
    val base = floor(w.camY).toInt()

    for (r in base..base + ROWS) {
        val lane = w.laneAt(r)
        val top = (w.camY + ROWS - (r + 1)) * cell
        val isRoad = lane.kind == LaneKind.ROAD
        drawRect(
            color = when {
                r == TARGET_ROW -> GOAL
                isRoad -> ROAD
                else -> GRASS
            },
            topLeft = Offset(0f, top), size = Size(size.width, cell)
        )
        if (isRoad) {
            drawRect(Color.White.copy(alpha = 0.25f), Offset(0f, top + cell - 1.5f), Size(size.width, 3f))
            for (i in 0 until lane.count) {
                val vx = w.vehicleX(lane, i, w.t) * cell
                drawRoundRect(
                    color = lane.color,
                    topLeft = Offset(vx, top + 0.18f * cell),
                    size = Size(lane.len * cell, 0.64f * cell),
                    cornerRadius = CornerRadius(cell * 0.12f)
                )
                // faros en el frente para saber hacia dónde va
                val lightX = if (lane.dir > 0) vx + lane.len * cell - cell * 0.12f else vx
                drawRect(Color.White.copy(alpha = 0.85f), Offset(lightX, top + 0.30f * cell), Size(cell * 0.12f, 0.40f * cell))
            }
        }
    }

    // sapito
    val cx = w.fx * cell
    val cy = (w.camY + ROWS - w.fy) * cell
    val squash = 1f + 0.25f * sin(PI.toFloat() * w.hopT)
    val body = if (w.dead) FROG_DEAD else FROG
    drawCircle(body, radius = cell * 0.30f * squash, center = Offset(cx, cy))
    drawCircle(Color.White, radius = cell * 0.09f, center = Offset(cx - cell * 0.13f, cy - cell * 0.12f))
    drawCircle(Color.White, radius = cell * 0.09f, center = Offset(cx + cell * 0.13f, cy - cell * 0.12f))
    drawCircle(Color.Black, radius = cell * 0.04f, center = Offset(cx - cell * 0.13f, cy - cell * 0.12f))
    drawCircle(Color.Black, radius = cell * 0.04f, center = Offset(cx + cell * 0.13f, cy - cell * 0.12f))

    if (w.popupTtl > 0f) {
        w.popupText?.let {
            drawText(
                measurer, it, Offset(size.width * 0.12f, size.height * 0.08f),
                TextStyle(color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            )
        }
    }
}
