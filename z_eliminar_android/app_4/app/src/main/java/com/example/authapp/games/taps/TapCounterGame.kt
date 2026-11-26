package com.example.authapp.games.taps

import android.os.SystemClock
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.authapp.core.game.GameModule
import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import kotlinx.coroutines.delay
import java.util.Locale

/** Contador de toques contrarreloj. Puntaje = cantidad de toques en el tiempo dado. */
object TapCounterGame : GameModule {
    override val slug = "taps"
    override val title = "Contador de toques"
    override val description = "Toca lo más rápido posible en 10 segundos."

    @Composable
    override fun Content(
        session: GameSession,
        onFinished: (GameResult) -> Unit,
        onExit: () -> Unit
    ) {
        val totalMs = (session.config["duration_seconds"]?.toLongOrNull() ?: 10L) * 1000L
        var running by remember { mutableStateOf(false) }
        var taps by remember { mutableIntStateOf(0) }
        var remainingMs by remember { mutableLongStateOf(totalMs) }
        val currentOnFinished by rememberUpdatedState(onFinished)

        LaunchedEffect(running) {
            if (!running) return@LaunchedEffect
            val start = SystemClock.elapsedRealtime()
            while (true) {
                val left = totalMs - (SystemClock.elapsedRealtime() - start)
                if (left <= 0) break
                remainingMs = left
                delay(50)
            }
            remainingMs = 0
            running = false
            currentOnFinished(GameResult(score = taps, durationMs = totalMs))
        }

        Column(
            modifier = Modifier.fillMaxSize().padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Row(Modifier.fillMaxWidth()) { TextButton(onClick = onExit) { Text("Salir") } }
            Text("Contador de toques", style = MaterialTheme.typography.headlineSmall)
            Text(
                String.format(Locale.US, "Tiempo: %.1f s", remainingMs / 1000.0),
                style = MaterialTheme.typography.titleLarge
            )
            Text("Toques: $taps", style = MaterialTheme.typography.displayMedium, fontWeight = FontWeight.Bold)

            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .clip(RoundedCornerShape(24.dp))
                    .background(
                        if (running) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.surfaceVariant
                    )
                    .clickable(enabled = running) { taps++ },
                contentAlignment = Alignment.Center
            ) {
                if (running) {
                    Text("¡TOCA!", style = MaterialTheme.typography.displayLarge, color = MaterialTheme.colorScheme.onPrimary)
                } else {
                    Button(onClick = { running = true }) { Text("Empezar (${totalMs / 1000} s)") }
                }
            }
        }
    }
}
