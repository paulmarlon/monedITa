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

/** Host: pide sesión, corre el módulo del juego y reporta el resultado. No contiene lógica de ningún juego. */
@Composable
fun GamePlayScreen(
    playVm: PlayViewModel,
    registry: GameRegistry,
    onFinished: () -> Unit,
    onExit: () -> Unit
) {
    val state by playVm.state.collectAsStateWithLifecycle()

    LaunchedEffect(state) {
        if (state is PlayState.Finished) onFinished()
    }

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
        PlayState.Submitting -> CenteredLoading("Guardando puntaje…")
        is PlayState.SubmitFailed -> Column(
            Modifier.fillMaxSize().padding(24.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text("Hiciste ${s.result.score} puntos", style = MaterialTheme.typography.titleLarge)
            Text("No se pudo guardar: ${s.message}")
            Button(onClick = playVm::retrySubmit) { Text("Reintentar envío") }
            OutlinedButton(onClick = onExit) { Text("Salir sin guardar") }
        }
        is PlayState.Finished -> CenteredLoading()
    }
}
