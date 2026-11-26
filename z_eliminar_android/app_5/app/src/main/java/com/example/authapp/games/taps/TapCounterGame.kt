package com.example.authapp.games.taps

import android.os.SystemClock
import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.authapp.core.game.GameModule
import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.games.common.GameTopBar
import com.example.authapp.games.common.StartCountdown
import kotlinx.coroutines.delay
import java.util.Locale

private enum class TapPhase { READY, COUNTDOWN, RUNNING, DONE }

/** Contador de toques contrarreloj. Puntaje = cantidad de toques en el tiempo dado. */
object TapCounterGame : GameModule {
    override val slug = "taps"
    override val title = "Contador de toques"
    override val description = "Toca la pantalla lo más rápido posible en 10 segundos."

    @Composable
    override fun Content(
        session: GameSession,
        onFinished: (GameResult) -> Unit,
        onExit: () -> Unit
    ) {
        val totalMs = (session.config["duration_seconds"]?.toLongOrNull() ?: 10L) * 1000L
        var phase by remember { mutableStateOf(TapPhase.READY) }
        var taps by remember { mutableIntStateOf(0) }
        var remainingMs by remember { mutableLongStateOf(totalMs) }
        val currentOnFinished by rememberUpdatedState(onFinished)
        val phaseNow by rememberUpdatedState(phase)

        LaunchedEffect(phase) {
            if (phase != TapPhase.RUNNING) return@LaunchedEffect
            val start = SystemClock.elapsedRealtime()
            while (true) {
                val left = totalMs - (SystemClock.elapsedRealtime() - start)
                if (left <= 0L) break
                remainingMs = left
                delay(33)
            }
            remainingMs = 0L
            phase = TapPhase.DONE                       // bloquea más toques y evita doble envío
            currentOnFinished(GameResult(score = taps, durationMs = totalMs))
        }

        Column(
            modifier = Modifier.fillMaxSize().padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            GameTopBar(title, "Toques: $taps", onExit)
            Text(
                String.format(Locale.US, "Tiempo: %.1f s", remainingMs / 1000.0),
                style = MaterialTheme.typography.titleLarge
            )

            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .clip(RoundedCornerShape(24.dp))
                    .background(
                        if (phase == TapPhase.RUNNING) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.surfaceVariant
                    )
                    // Cuenta al TOCAR (no al soltar): permite más toques por segundo
                    .pointerInput(Unit) {
                        detectTapGestures(onPress = { if (phaseNow == TapPhase.RUNNING) taps++ })
                    },
                contentAlignment = Alignment.Center
            ) {
                when (phase) {
                    TapPhase.READY -> Column(
                        verticalArrangement = Arrangement.spacedBy(12.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text("Toca la pantalla lo más rápido posible", style = MaterialTheme.typography.titleMedium)
                        Button(onClick = { phase = TapPhase.COUNTDOWN }) { Text("Empezar (${totalMs / 1000} s)") }
                    }
                    TapPhase.COUNTDOWN -> StartCountdown(color = MaterialTheme.colorScheme.onSurfaceVariant) {
                        remainingMs = totalMs
                        phase = TapPhase.RUNNING
                    }
                    TapPhase.RUNNING -> Text(
                        "¡TOCA!",
                        style = MaterialTheme.typography.displayLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                    TapPhase.DONE -> Text("¡Tiempo!", style = MaterialTheme.typography.displayMedium)
                }
            }
        }
    }
}
