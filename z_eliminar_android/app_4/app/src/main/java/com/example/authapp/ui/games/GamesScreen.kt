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
import com.example.authapp.ui.common.creditsText
import com.example.authapp.ui.main.CatalogItem

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GamesScreen(items: List<CatalogItem>, onOpen: (slug: String) -> Unit) {
    LazyVerticalGrid(
        columns = GridCells.Fixed(2),
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            Text("Juegos", style = MaterialTheme.typography.headlineSmall)
        }
        if (items.isEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) { Text("No hay juegos disponibles.") }
        }
        items(items, key = { it.info.slug }) { item ->
            val card: @Composable ColumnScope.() -> Unit = {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(item.info.title, style = MaterialTheme.typography.titleMedium)
                    Text(item.info.description, style = MaterialTheme.typography.bodySmall)
                    item.info.cost?.takeIf { it > 0 }?.let {
                        Text("Costo: ${creditsText(it)} crédito(s)", style = MaterialTheme.typography.labelMedium)
                    }
                    Text(
                        item.badge ?: "Toca para ver detalle",
                        style = MaterialTheme.typography.labelMedium,
                        color = if (item.playable) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.error
                    )
                }
            }
            if (item.playable) {
                Card(onClick = { onOpen(item.info.slug) }, modifier = Modifier.fillMaxWidth(), content = card)
            } else {
                Card(modifier = Modifier.fillMaxWidth(), content = card) // no jugable: sin clic
            }
        }
    }
}
