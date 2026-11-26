package com.example.authapp.ui.common

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp

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
fun SectionTitle(text: String) =
    Text(text, style = MaterialTheme.typography.titleMedium)

fun rankText(rank: Int?, outOf: Int?): String =
    if (rank == null) "Ranking: —" else "Ranking: #$rank" + (outOf?.let { " de $it" } ?: "")
