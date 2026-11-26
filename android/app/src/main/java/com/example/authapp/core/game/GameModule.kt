package com.example.authapp.core.game

import androidx.compose.runtime.Composable

/** Lo único que un juego recibe: la partida emitida por el servidor (partida_id) + configuración mínima. */
data class GameSession(
    val sessionId: String,
    val slug: String,
    val config: Map<String, String> = emptyMap()
)

/** Lo único que un juego devuelve. No sabe nada de red, tokens ni ciclos. */
data class GameResult(val score: Int, val durationMs: Long)

interface GameModule {
    /** Debe coincidir con `juegos.slug` en la base de datos. */
    val slug: String
    val title: String
    val description: String

    @Composable
    fun Content(
        session: GameSession,
        onFinished: (GameResult) -> Unit,
        onExit: () -> Unit
    )
}

class GameRegistry(modules: Set<GameModule>) {
    private val bySlug = modules.associateBy { it.slug }
    fun find(slug: String): GameModule? = bySlug[slug]
    fun all(): List<GameModule> = bySlug.values.toList()
    fun slugs(): List<String> = bySlug.keys.toList()
}
