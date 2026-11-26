package com.example.authapp.ui.games

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.ui.common.CenteredLoading
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.main.PlayState
import com.example.authapp.ui.main.PlayViewModel

/**
 * Host: corre el módulo del juego. Al terminar (cuando empieza el envío) pasa a la pantalla de resultado,
 * que se encarga del envío, los reintentos y los errores. No contiene lógica de ningún juego.
 */
@Composable
fun GamePlayScreen(
    playVm: PlayViewModel,
    registry: GameRegistry,
    onGameDone: () -> Unit,
    onExit: () -> Unit
) {
    val state by playVm.state.collectAsStateWithLifecycle()

    // Disparador de una sola vez (no depende del estado exacto posterior)
    val done = state is PlayState.Submitting || state is PlayState.SubmitFailed || state is PlayState.Finished
    LaunchedEffect(done) { if (done) onGameDone() }

    when (val s = state) {
        PlayState.Idle -> CenteredMessage("Sin partida en curso", "Vuelve al catálogo para empezar una.", "Volver", onExit)
        PlayState.Starting -> CenteredLoading("Preparando partida…")
        is PlayState.StartFailed -> Column(
            Modifier.fillMaxSize().padding(24.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text("No se pudo iniciar la partida", style = MaterialTheme.typography.titleLarge)
            Text(s.message)
            Button(onClick = { playVm.start(s.slug) }) { Text("Reintentar") }
            OutlinedButton(onClick = onExit) { Text("Volver") }
        }
        is PlayState.Playing -> {
            val module = registry.find(s.session.slug)
            if (module != null) {
                module.Content(
                    session = s.session,
                    onFinished = playVm::onGameFinished,
                    onExit = onExit
                )
            } else {
                CenteredMessage("Juego no disponible", "Actualiza la app para jugar esto.", "Volver", onExit)
            }
        }
        PlayState.Submitting, is PlayState.SubmitFailed, is PlayState.Finished -> CenteredLoading()
    }
}
