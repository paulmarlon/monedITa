package com.example.authapp.ui.games

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.common.rankText
import com.example.authapp.ui.main.PlayState

@Composable
fun GameResultScreen(
    finished: PlayState.Finished?,
    onPlayAgain: (slug: String) -> Unit,
    onBack: () -> Unit
) {
    BackHandler(onBack = onBack)

    if (finished == null) {
        CenteredMessage("Sin resultado", "No hay una partida reciente.", "Volver", onBack)
        return
    }
    val o = finished.outcome

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
        Text(rankText(o.rank, o.totalPlayers).replace("Ranking", "Ranking individual"))

        Card(Modifier.fillMaxWidth()) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                val t = o.team
                if (t != null) {
                    Text("Equipo ${t.name}", style = MaterialTheme.typography.titleMedium)
                    Text("Aportaste +${o.score} · Total del equipo: ${t.teamTotal}")
                    Text(rankText(t.rank, t.totalTeams).replace("Ranking", "Ranking de equipos"))
                } else {
                    Text("Aún no tienes equipo en este ciclo. Tu puntaje cuenta solo de forma individual.")
                }
            }
        }

        Spacer(Modifier.height(8.dp))
        Button(onClick = { onPlayAgain(finished.session.slug) }, modifier = Modifier.fillMaxWidth()) { Text("Jugar de nuevo") }
        OutlinedButton(onClick = onBack, modifier = Modifier.fillMaxWidth()) { Text("Volver") }
    }
}
