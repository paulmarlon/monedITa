package com.example.authapp.ui.games

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.example.authapp.data.game.GameRanking
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.common.RankingBlock
import com.example.authapp.ui.common.SectionTitle
import com.example.authapp.ui.common.creditsText
import com.example.authapp.ui.main.CatalogItem

@Composable
fun GameDetailScreen(
    item: CatalogItem?,
    ranking: GameRanking?,
    credits: Double?,
    onPlay: () -> Unit,
    onBack: () -> Unit
) {
    if (item == null) {
        CenteredMessage("Juego no encontrado", "Vuelve al catálogo.", "Volver", onBack)
        return
    }
    val cost = item.info.cost
    val noCredits = cost != null && cost > 0 && credits != null && credits < cost

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        TextButton(onClick = onBack) { Text("← Volver") }
        Text(item.info.title, style = MaterialTheme.typography.headlineSmall)
        Text(item.info.description)
        Text(
            if (cost != null && cost > 0) "Costo: ${creditsText(cost)} crédito(s) por partida"
            else "Si el juego tiene costo, se descuenta de tus créditos al iniciar la partida.",
            style = MaterialTheme.typography.bodyMedium
        )
        credits?.let { Text("Tus créditos: ${creditsText(it)}", style = MaterialTheme.typography.bodyMedium) }

        Button(onClick = onPlay, enabled = item.playable && !noCredits, modifier = Modifier.fillMaxWidth()) {
            Text(
                when {
                    !item.playable -> item.badge ?: "No disponible"
                    noCredits -> "Créditos insuficientes"
                    else -> "Jugar"
                }
            )
        }

        SectionTitle("Ranking")
        if (ranking != null) RankingBlock(ranking) else Text("Aún sin ranking.", style = MaterialTheme.typography.bodySmall)
    }
}
