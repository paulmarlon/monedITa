package com.example.authapp.games.common

import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.delay

/** Máximo delta por frame: tras una pausa o un tirón el juego no "salta" ni atraviesa obstáculos. */
private const val MAX_FRAME_DT = 0.05f

/**
 * Bucle de juego sincronizado con el refresco de pantalla (≈60 FPS).
 * [onFrame] recibe el delta en SEGUNDOS (acotado a [MAX_FRAME_DT]). Se detiene solo cuando [running] es false
 * o el composable sale de pantalla.
 */
@Composable
fun GameFrameLoop(running: Boolean, onFrame: (dt: Float) -> Unit) {
    val frame by rememberUpdatedState(onFrame)
    LaunchedEffect(running) {
        if (!running) return@LaunchedEffect
        var last = withFrameNanos { it }
        while (true) {
            withFrameNanos { now ->
                val dt = ((now - last) / 1_000_000_000f).coerceIn(0f, MAX_FRAME_DT)
                last = now
                frame(dt)
            }
        }
    }
}

/** "Salir" abandona SIN guardar. [onFinish] (si existe) termina la partida y GUARDA el puntaje actual. */
@Composable
fun GameTopBar(title: String, status: String, onExit: () -> Unit, onFinish: (() -> Unit)? = null) {
    Row(
        Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        TextButton(onClick = onExit) { Text("Salir") }
        Text(
            title,
            style = MaterialTheme.typography.titleMedium,
            modifier = Modifier.weight(1f),
            textAlign = androidx.compose.ui.text.style.TextAlign.Center,
            maxLines = 1,
            overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
        )
        Text(status, style = MaterialTheme.typography.titleMedium)
        if (onFinish != null) TextButton(onClick = onFinish) { Text("Terminar") }
    }
}

/** Cuenta regresiva 3-2-1 y luego llama a [onDone]. */
@Composable
fun StartCountdown(seconds: Int = 3, color: Color = Color.White, onDone: () -> Unit) {
    var n by remember { mutableIntStateOf(seconds) }
    val done by rememberUpdatedState(onDone)
    LaunchedEffect(Unit) {
        while (n > 0) {
            delay(1000)
            n -= 1
        }
        done()
    }
    Text(
        if (n > 0) n.toString() else "¡YA!",
        style = MaterialTheme.typography.displayLarge,
        fontWeight = FontWeight.Bold,
        color = color
    )
}

/** Capa semitransparente sobre el área de juego (instrucciones, cuenta regresiva, fin de partida). */
@Composable
fun GameOverlay(modifier: Modifier = Modifier, content: @Composable ColumnScope.() -> Unit) {
    Column(
        modifier.background(Color.Black.copy(alpha = 0.55f)).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally,
        content = content
    )
}

/** Botón que reacciona al TOCAR y al SOLTAR (necesario para saltos de altura variable y caída rápida). */
@Composable
fun HoldPad(label: String, onDown: () -> Unit, onUp: () -> Unit, modifier: Modifier = Modifier) {
    var pressed by remember { mutableStateOf(false) }
    val down by rememberUpdatedState(onDown)
    val up by rememberUpdatedState(onUp)
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(20.dp))
            .background(
                if (pressed) MaterialTheme.colorScheme.primary
                else MaterialTheme.colorScheme.primaryContainer
            )
            .pointerInput(Unit) {
                detectTapGestures(onPress = {
                    pressed = true
                    down()
                    try {
                        tryAwaitRelease()
                    } finally {
                        pressed = false
                        up()
                    }
                })
            },
        contentAlignment = Alignment.Center
    ) {
        Text(
            label,
            style = MaterialTheme.typography.titleLarge,
            color = if (pressed) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onPrimaryContainer
        )
    }
}
