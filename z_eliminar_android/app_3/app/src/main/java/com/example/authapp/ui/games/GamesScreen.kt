package com.example.authapp.ui.games

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.data.game.GameInfo
import com.example.authapp.data.game.GameStatus
import com.example.authapp.ui.common.CenteredLoading
import com.example.authapp.ui.common.CenteredMessage
import com.example.authapp.ui.main.CatalogUiState

@Composable
fun GamesScreen(
    state: CatalogUiState,
    registry: GameRegistry,
    onRetry: () -> Unit,
    onPlay: (slug: String) -> Unit
) {
    when (state) {
        CatalogUiState.Loading -> CenteredLoading()
        is CatalogUiState.Error -> CenteredMessage("No se pudo cargar el catálogo", state.message, "Reintentar", onRetry)
        is CatalogUiState.Content -> LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                Text("Juegos", style = MaterialTheme.typography.headlineSmall)
            }
            items(state.games, key = { it.slug }) { game ->
                GameCard(game, registry.supports(game.slug), onPlay)
            }
        }
    }
}

@Composable
private fun GameCard(game: GameInfo, supported: Boolean, onPlay: (String) -> Unit) {
    val playable = game.status == GameStatus.AVAILABLE && supported
    val label = when {
        game.status == GameStatus.COMING_SOON -> "Próximamente"
        game.status == GameStatus.MAINTENANCE -> "Mantenimiento"
        !supported -> "Actualiza la app"
        else -> "Jugar"
    }
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text(game.name, style = MaterialTheme.typography.titleMedium)
            Text(game.description, style = MaterialTheme.typography.bodySmall)
            Text(
                game.myBestScore?.let { "Tu mejor: $it" } ?: "Sin partidas",
                style = MaterialTheme.typography.labelMedium
            )
            Button(onClick = { onPlay(game.slug) }, enabled = playable, modifier = Modifier.fillMaxWidth()) {
                Text(label)
            }
        }
    }
}
