package com.example.authapp.ui.common

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.example.authapp.data.game.GameRanking
import java.util.Locale

@Composable
fun CenteredLoading(label: String? = null) {
    Column(
        Modifier.fillMaxSize(),
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        CircularProgressIndicator()
        if (label != null) Text(label)
    }
}

@Composable
fun CenteredMessage(
    title: String,
    message: String,
    actionLabel: String? = null,
    onAction: () -> Unit = {}
) {
    Column(
        Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(title, style = MaterialTheme.typography.titleLarge)
        Text(message, textAlign = TextAlign.Center)
        if (actionLabel != null) Button(onClick = onAction) { Text(actionLabel) }
    }
}

@Composable
fun SectionTitle(text: String) = Text(text, style = MaterialTheme.typography.titleMedium)

@Composable
fun StatCard(title: String, big: String, lines: List<String>, modifier: Modifier = Modifier) {
    Card(modifier) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(title, style = MaterialTheme.typography.labelLarge)
            Text(big, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
            lines.forEach { Text(it, style = MaterialTheme.typography.bodySmall) }
        }
    }
}

/** Tu mejor puntaje/posición + top del juego. */
@Composable
fun RankingBlock(g: GameRanking) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Text(
            if (g.myBest == null) "Aún no has jugado"
            else "Tu mejor: ${g.myBest}" + (g.myPosition?.let { " · Posición #$it" } ?: ""),
            style = MaterialTheme.typography.bodyMedium
        )
        if (g.top.isEmpty()) {
            Text("Nadie ha jugado todavía. ¡Sé el primero!", style = MaterialTheme.typography.bodySmall)
        } else {
            g.top.forEach { e ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("#${e.position}  ${e.alias}" + if (e.isMe) " (tú)" else "", style = MaterialTheme.typography.bodySmall)
                    Text(
                        e.score.toString(),
                        style = MaterialTheme.typography.bodySmall,
                        fontWeight = if (e.isMe) FontWeight.Bold else FontWeight.Normal
                    )
                }
            }
        }
    }
}

fun rankText(rank: Int, outOf: Int?): String = "Ranking: #$rank" + (outOf?.let { " de $it" } ?: "")

fun creditsText(v: Double): String =
    if (v % 1.0 == 0.0) v.toInt().toString() else String.format(Locale.US, "%.2f", v)
