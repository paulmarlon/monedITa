package com.example.authapp.core.game

import androidx.compose.runtime.Composable

/** Lo único que un juego recibe: sesión emitida por el servidor + configuración mínima. */
data class GameSession(
    val sessionId: String,
    val slug: String,
    val config: Map<String, String> = emptyMap()
)

/** Lo único que un juego devuelve. No sabe nada de red, tokens ni ciclos. */
data class GameResult(val score: Int, val durationMs: Long)

interface GameModule {
    val slug: String

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
    fun supports(slug: String): Boolean = slug in bySlug
}
