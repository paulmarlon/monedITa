package com.example.authapp.ui.dashboard

import android.text.format.DateUtils
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.authapp.data.game.DashboardData
import com.example.authapp.ui.common.CenteredLoading
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.common.SectionTitle
import com.example.authapp.ui.common.rankText
import com.example.authapp.ui.main.DashboardUiState

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(state: DashboardUiState, onRefresh: () -> Unit, onGoToGames: () -> Unit) {
    when (state) {
        DashboardUiState.Loading -> CenteredLoading()
        is DashboardUiState.Error -> CenteredMessage("No se pudo cargar el tablero", state.message, "Reintentar", onRefresh)
        DashboardUiState.NoActiveCycle -> CenteredMessage(
            "Sin ciclo activo",
            "Cuando se abra un nuevo ciclo podrás ver tu puntaje y jugar."
        )
        is DashboardUiState.Content -> PullToRefreshBox(isRefreshing = state.refreshing, onRefresh = onRefresh) {
            DashboardContent(state.data, onGoToGames)
        }
    }
}

@Composable
private fun DashboardContent(data: DashboardData, onGoToGames: () -> Unit) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item {
            Column {
                Text(data.cycle.name, style = MaterialTheme.typography.headlineSmall)
                val d = data.cycle.daysLeft
                Text("Quedan ${if (d == 1) "1 día" else "$d días"}", style = MaterialTheme.typography.bodyMedium)
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                val ind = data.individual
                StatCard(
                    "Individual", ind.total.toString(),
                    listOf("Mejor: ${ind.best}", rankText(ind.rank, ind.totalPlayers)),
                    Modifier.weight(1f)
                )
                val t = data.team
                if (t != null) {
                    StatCard(
                        t.name, t.total.toString(),
                        listOf("Tu aporte: ${t.myContribution}", rankText(t.rank, t.totalTeams)),
                        Modifier.weight(1f)
                    )
                } else {
                    StatCard("Equipo", "—", listOf("Aún no tienes equipo en este ciclo"), Modifier.weight(1f))
                }
            }
        }

        item { SectionTitle("Tus juegos") }
        if (data.games.isEmpty()) {
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Aún no jugaste en este ciclo.")
                        Button(onClick = onGoToGames) { Text("Ir a Juegos") }
                    }
                }
            }
        } else {
            items(data.games, key = { it.slug }) { g ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Column {
                        Text(g.name)
                        Text("${g.plays} partidas", style = MaterialTheme.typography.bodySmall)
                    }
                    Text("Mejor: ${g.bestScore}", fontWeight = FontWeight.Bold)
                }
            }
        }

        if (data.recent.isNotEmpty()) {
            item { SectionTitle("Actividad reciente") }
            items(data.recent, key = { "recent-${it.id}" }) { e ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Column {
                        Text(e.gameName)
                        Text(
                            DateUtils.getRelativeTimeSpanString(
                                e.playedAtMillis, System.currentTimeMillis(), DateUtils.MINUTE_IN_MILLIS
                            ).toString(),
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                    Text((if (e.isPersonalBest) "★ " else "") + e.score, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
private fun StatCard(title: String, big: String, lines: List<String>, modifier: Modifier = Modifier) {
    Card(modifier) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(title, style = MaterialTheme.typography.labelLarge)
            Text(big, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
            lines.forEach { Text(it, style = MaterialTheme.typography.bodySmall) }
        }
    }
}
