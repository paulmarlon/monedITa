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
import com.example.authapp.data.game.ScoreEntry
import com.example.authapp.ui.common.CenteredLoading
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.common.RankingBlock
import com.example.authapp.ui.common.SectionTitle
import com.example.authapp.ui.common.StatCard
import com.example.authapp.ui.common.creditsText
import com.example.authapp.ui.common.rankText
import com.example.authapp.ui.main.DashboardUiState

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    state: DashboardUiState,
    creditsOverride: Double?,
    recent: List<ScoreEntry>,
    titleOf: (slug: String) -> String,
    onRefresh: () -> Unit,
    onGoToGames: () -> Unit
) {
    when (state) {
        DashboardUiState.Loading -> CenteredLoading()
        DashboardUiState.NoActiveCycle -> CenteredMessage(
            "Sin ciclo activo",
            "Cuando se abra un nuevo ciclo podrás ver tus créditos y puntajes, y jugar.",
            "Actualizar", onRefresh
        )
        is DashboardUiState.Error -> CenteredMessage("No se pudo cargar el tablero", state.message, "Reintentar", onRefresh)
        is DashboardUiState.Content -> PullToRefreshBox(isRefreshing = state.refreshing, onRefresh = onRefresh) {
            DashboardContent(state.data, creditsOverride, recent, titleOf, onGoToGames)
        }
    }
}

@Composable
private fun DashboardContent(
    data: DashboardData,
    creditsOverride: Double?,
    recent: List<ScoreEntry>,
    titleOf: (String) -> String,
    onGoToGames: () -> Unit
) {
    val credits = creditsOverride ?: data.credits

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Cabecera: solo lo que existe
        item {
            Column {
                Text(data.cycle?.name ?: "Tablero", style = MaterialTheme.typography.headlineSmall)
                data.cycle?.daysLeft?.let {
                    Text("Quedan ${if (it == 1) "1 día" else "$it días"}", style = MaterialTheme.typography.bodyMedium)
                }
            }
        }

        credits?.let {
            item {
                Card(Modifier.fillMaxWidth()) {
                    Row(Modifier.padding(16.dp).fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("Créditos disponibles")
                        Text(creditsText(it), fontWeight = FontWeight.Bold)
                    }
                }
            }
        }

        if (data.individual != null || data.team != null) {
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    data.individual?.let { ind ->
                        StatCard(
                            "Individual", ind.total.toString(),
                            listOfNotNull(ind.best?.let { "Mejor: $it" }, ind.rank?.let { rankText(it, ind.totalPlayers) }),
                            Modifier.weight(1f)
                        )
                    }
                    data.team?.let { t ->
                        StatCard(
                            t.name, t.total.toString(),
                            listOfNotNull(t.myContribution?.let { "Tu aporte: $it" }, t.rank?.let { rankText(it, t.totalTeams) }),
                            Modifier.weight(1f)
                        )
                    }
                }
            }
        }

        item { SectionTitle("Rankings") }
        if (data.games.isEmpty()) {
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Todavía no hay actividad ni rankings.")
                        Button(onClick = onGoToGames) { Text("Ir a Juegos") }
                    }
                }
            }
        } else {
            items(data.games, key = { it.slug }) { g ->
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Text(g.title, style = MaterialTheme.typography.titleMedium)
                        RankingBlock(g)
                    }
                }
            }
        }

        if (recent.isNotEmpty()) {
            item { SectionTitle("Actividad reciente (esta sesión)") }
            items(recent, key = { "recent-${it.id}" }) { e ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Column {
                        Text(titleOf(e.gameSlug))
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
