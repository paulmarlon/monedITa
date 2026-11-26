package com.example.authapp.data.game

import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.delay
import java.util.UUID

/**
 * Datos de prueba en memoria. Prueba los estados cambiando los parámetros:
 *   FakeGameRepository(hasCycle = false)  -> "Sin ciclo activo"
 *   FakeGameRepository(hasTeam = false)   -> usuario sin equipo
 */
class FakeGameRepository(
    private val hasCycle: Boolean = true,
    private val hasTeam: Boolean = true
) : GameRepository {

    private data class Played(val id: Long, val slug: String, val score: Int, val at: Long, val pb: Boolean)

    private val played = mutableListOf<Played>()
    private val openSessions = mutableSetOf<String>()
    private var nextId = 1L
    private val rivals = listOf(1200, 800, 450, 200)
    private val teamRivals = listOf(1500, 900, 600)
    private val names = mapOf("taps" to "Contador de toques", "memoria" to "Memoria")

    private fun nameOf(slug: String) = names[slug] ?: slug
    private fun total() = played.sumOf { it.score }

    private fun individualRank() = 1 + rivals.count { it > total() }
    private fun teamTotal() = total() + 300
    private fun teamRank() = 1 + teamRivals.count { it > teamTotal() }

    override suspend fun dashboard(): ApiResult<DashboardData?> {
        delay(400)
        if (!hasCycle) return ApiResult.Success(null)
        val games = played.groupBy { it.slug }.map { (slug, list) ->
            GameScoreSummary(slug, nameOf(slug), list.maxOf { it.score }, list.size)
        }
        val recent = played.takeLast(10).reversed().map {
            ScoreEntry(it.id, it.slug, nameOf(it.slug), it.score, it.at, it.pb)
        }
        val team = if (hasTeam) TeamScore(
            teamId = 2, name = "Los Tigres", total = teamTotal(),
            myContribution = total(), rank = teamRank(), totalTeams = teamRivals.size + 1
        ) else null
        return ApiResult.Success(
            DashboardData(
                cycle = Cycle(12, "Ciclo 2/2026", 19),
                individual = IndividualScore(
                    total = total(), best = played.maxOfOrNull { it.score } ?: 0,
                    gamesPlayed = played.size, rank = individualRank(), totalPlayers = rivals.size + 1
                ),
                team = team, games = games, recent = recent
            )
        )
    }

    override suspend fun games(): ApiResult<List<GameInfo>> {
        delay(300)
        fun best(slug: String) = played.filter { it.slug == slug }.maxOfOrNull { it.score }
        return ApiResult.Success(
            listOf(
                GameInfo("taps", "Contador de toques", "Toca lo más rápido posible en 10 segundos.", GameStatus.AVAILABLE, best("taps")),
                GameInfo("memoria", "Memoria", "Encuentra los pares.", GameStatus.COMING_SOON, null)
            )
        )
    }

    override suspend fun createSession(slug: String): ApiResult<GameSession> {
        delay(200)
        if (!hasCycle) return ApiResult.Error("No hay un ciclo activo.")
        val id = UUID.randomUUID().toString()
        openSessions += id
        return ApiResult.Success(GameSession(id, slug, mapOf("duration_seconds" to "10")))
    }

    override suspend fun submitScore(session: GameSession, result: GameResult): ApiResult<SubmitResult> {
        delay(300)
        // Igual que el backend: cada sesión se acepta una sola vez.
        if (!openSessions.remove(session.sessionId)) {
            return ApiResult.Error("Esta partida ya fue registrada o expiró.")
        }
        val prevBest = played.filter { it.slug == session.slug }.maxOfOrNull { it.score } ?: -1
        val pb = result.score > prevBest
        played += Played(nextId++, session.slug, result.score, System.currentTimeMillis(), pb)
        return ApiResult.Success(
            SubmitResult(
                score = result.score, isPersonalBest = pb, bestScore = maxOf(prevBest, result.score),
                rank = individualRank(), totalPlayers = rivals.size + 1,
                team = if (hasTeam) TeamImpact("Los Tigres", teamTotal(), teamRank(), teamRivals.size + 1) else null
            )
        )
    }
}
