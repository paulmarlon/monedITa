package com.example.authapp.ui.games

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.authapp.ui.common.CenteredLoading
import com.example.authapp.ui.common.creditsText
import com.example.authapp.ui.common.rankText
import com.example.authapp.ui.main.PlayState

/** Envío del puntaje + resultado. Conserva partida y resultado si el envío falla. */
@Composable
fun GameResultScreen(
    state: PlayState,
    onRetrySubmit: () -> Unit,
    onPlayAgain: (slug: String) -> Unit,
    onBack: () -> Unit
) {
    BackHandler(onBack = onBack)

    when (state) {
        PlayState.Submitting -> CenteredLoading("Guardando puntaje…")
        is PlayState.SubmitFailed -> SubmitFailedContent(state, onRetrySubmit, onBack)
        is PlayState.Finished -> FinishedContent(state, onPlayAgain, onBack)
        else -> Box(Modifier.fillMaxSize()) // transición (p. ej. "Jugar de nuevo")
    }
}

@Composable
private fun SubmitFailedContent(s: PlayState.SubmitFailed, onRetry: () -> Unit, onBack: () -> Unit) {
    Column(
        Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("Hiciste ${s.result.score} puntos", style = MaterialTheme.typography.headlineMedium)
        Text(
            if (s.possiblySaved) "Puede que ya esté guardado" else "No se pudo guardar el puntaje",
            style = MaterialTheme.typography.titleMedium
        )
        Text(s.message, textAlign = TextAlign.Center)

        if (s.possiblySaved) {
            Button(onClick = onBack, modifier = Modifier.fillMaxWidth()) { Text("Volver y revisar el ranking") }
        } else {
            Button(onClick = onRetry, modifier = Modifier.fillMaxWidth()) { Text("Reintentar envío") }
            OutlinedButton(onClick = onBack, modifier = Modifier.fillMaxWidth()) { Text("Salir sin guardar") }
            Text(
                "Si sales sin guardar, este puntaje se pierde.",
                style = MaterialTheme.typography.bodySmall, textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
private fun FinishedContent(f: PlayState.Finished, onPlayAgain: (String) -> Unit, onBack: () -> Unit) {
    val o = f.outcome
    Column(
        Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("¡Partida terminada!", style = MaterialTheme.typography.headlineMedium)
        Text(o.score.toString(), style = MaterialTheme.typography.displayLarge, fontWeight = FontWeight.Bold)

        if (o.isPersonalBest) {
            Text("🏆 ¡Nuevo récord personal!", style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.primary)
        } else {
            Text("Tu mejor puntaje: ${o.bestScore}")
        }
        o.rank?.let { Text("Tu posición en este juego: #$it") }
        o.individual?.let { ind ->
            Text(
                "Individual del ciclo: ${ind.total}" + (ind.rank?.let { " · " + rankText(it, ind.totalPlayers) } ?: ""),
                style = MaterialTheme.typography.bodyMedium
            )
        }

        o.team?.let { t ->
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("Equipo ${t.name}", style = MaterialTheme.typography.titleMedium)
                    Text("Total del equipo: ${t.teamTotal}")
                    t.rank?.let { Text(rankText(it, t.totalTeams)) }
                }
            }
        }

        if (f.chipsUsed != null || f.creditsLeft != null) {
            Text(
                listOfNotNull(
                    f.chipsUsed?.let { "Créditos usados: ${creditsText(it)}" },
                    f.creditsLeft?.let { "Te quedan: ${creditsText(it)}" }
                ).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall
            )
        }

        Spacer(Modifier.height(8.dp))
        Button(onClick = { onPlayAgain(f.session.slug) }, modifier = Modifier.fillMaxWidth()) { Text("Jugar de nuevo") }
        OutlinedButton(onClick = onBack, modifier = Modifier.fillMaxWidth()) { Text("Volver") }
    }
}
