package com.example.authapp.data.game

import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.delay
import java.util.UUID

enum class FakeScenario {
    NORMAL,        // todo disponible
    NO_CYCLE,      // sin ciclo activo (dashboard "Sin ciclo activo", iniciar -> 409)
    NO_CREDITS,    // 0 créditos (iniciar -> 402)
    NO_TEAM,       // usuario sin equipo (no se muestra equipo)
    FLAKY_SUBMIT;  // el primer envío se guarda pero falla la red; el reintento da 403 ("podría haberse guardado")

    companion object {
        fun fromName(name: String): FakeScenario =
            values().firstOrNull { it.name.equals(name.trim(), ignoreCase = true) } ?: NORMAL
    }
}

/** Datos de prueba en memoria (se reinician al cerrar la app). El login sigue usando el backend real. */
class FakeGameRepository(private val scenario: FakeScenario = FakeScenario.NORMAL) : GameRepository {

    private data class Played(val id: Long, val slug: String, val score: Int, val at: Long, val pb: Boolean)

    private val played = mutableListOf<Played>()
    private val openSessions = mutableSetOf<String>()
    private val failedOnce = mutableSetOf<String>()
    private var nextId = 1L
    private var credits = if (scenario == FakeScenario.NO_CREDITS) 0.0 else 5.0
    private val hasCycle = scenario != FakeScenario.NO_CYCLE
    private val hasTeam = scenario != FakeScenario.NO_TEAM

    private val rivals = listOf(1200, 800, 450, 200)
    private val teamRivals = listOf(1500, 900, 600)
    private val titles = mapOf(
        "taps" to "Contador de toques", "cube_runner" to "Cube Runner Eco.",
        "sapito-avenida" to "Sapito sobre avenida", "memoria" to "Memoria", "trivia" to "Trivia"
    )

    /** Regla: suma del mejor puntaje de cada juego. */
    private fun total() = played.groupBy { it.slug }.values.sumOf { list -> list.maxOf { it.score } }
    private fun bestOf(slug: String) = played.filter { it.slug == slug }.maxOfOrNull { it.score }
    private fun individualRank() = 1 + rivals.count { it > total() }
    private fun teamTotal() = total() + 300
    private fun teamRank() = 1 + teamRivals.count { it > teamTotal() }

    private fun individual() = IndividualScore(
        total = total(), best = played.maxOfOrNull { it.score }, gamesPlayed = played.size,
        rank = individualRank(), totalPlayers = rivals.size + 1
    )

    private fun rankingFor(slug: String): GameRanking {
        val mine = bestOf(slug)
        val rows = buildList {
            add("sudo" to 900); add("ana" to 700); add("luis" to 500)
            if (mine != null) add("tú" to mine)
        }.sortedByDescending { it.second }
        val top = rows.mapIndexed { i, (alias, score) -> RankEntry(i + 1, alias, null, score, alias == "tú") }
        return GameRanking(slug, titles[slug] ?: slug, top, top.firstOrNull { it.isMe }?.position, mine)
    }

    override suspend fun dashboard(slugs: List<String>): ApiResult<DashboardData?> {
        delay(400)
        if (!hasCycle) return ApiResult.Success(null)
        return ApiResult.Success(
            DashboardData(
                cycle = Cycle(12, "Ciclo 2/2026", 19),
                credits = credits,
                individual = individual(),
                team = if (hasTeam) TeamScore(2, "Los Tigres", teamTotal(), total(), teamRank(), teamRivals.size + 1) else null,
                games = slugs.map { rankingFor(it) }
            )
        )
    }

    /** Incluye "trivia" (no instalado -> "Actualiza la app") y "memoria" (próximamente). */
    override suspend fun catalog(): ApiResult<List<GameInfo>?> {
        delay(300)
        return ApiResult.Success(
            listOf(
                GameInfo("taps", "Contador de toques", "Toca la pantalla lo más rápido posible en 10 segundos.", 1.0, GameStatus.AVAILABLE),
                GameInfo(
                    "cube_runner", "Cube Runner Eco.",
                    "El clásico juego del dinosaurio adaptado para la plataforma. Salta los obstáculos y acumula la mayor cantidad de puntos.",
                    1.0, GameStatus.AVAILABLE
                ),
                GameInfo("sapito-avenida", "Sapito sobre avenida", "Cruza la avenida esquivando los vehículos.", 1.0, GameStatus.AVAILABLE),
                GameInfo("trivia", "Trivia", "Juego que esta versión de la app no tiene instalado.", 1.0, GameStatus.AVAILABLE),
                GameInfo("memoria", "Memoria", "Encuentra los pares.", 1.0, GameStatus.COMING_SOON)
            )
        )
    }

    override suspend fun startGame(slug: String): ApiResult<StartedGame> {
        delay(250)
        if (!hasCycle) return ApiResult.Error("No hay un ciclo académico activo.", code = 409)
        if (credits < 1.0) return ApiResult.Error("Créditos insuficientes para iniciar la partida.", code = 402)
        credits -= 1.0
        val id = UUID.randomUUID().toString()
        openSessions += id
        return ApiResult.Success(StartedGame(GameSession(id, slug), chipsUsed = 1.0, creditsLeft = credits))
    }

    override suspend fun submitScore(session: GameSession, result: GameResult): ApiResult<SubmitResult> {
        delay(300)
        // Igual que el backend: cada partida se acepta una sola vez.
        if (!openSessions.remove(session.sessionId)) {
            return ApiResult.Error("Partida inválida, expirada o ya registrada.", code = 403)
        }
        val prevBest = bestOf(session.slug)
        val pb = prevBest == null || result.score > prevBest
        played += Played(nextId++, session.slug, result.score, System.currentTimeMillis(), pb)

        if (scenario == FakeScenario.FLAKY_SUBMIT && failedOnce.add(session.sessionId)) {
            // Se guardó, pero la respuesta "se perdió".
            return ApiResult.Error("No se pudo conectar con el servidor. Revisa tu conexión.", network = true)
        }
        return ApiResult.Success(
            SubmitResult(
                score = result.score, isPersonalBest = pb, bestScore = maxOf(prevBest ?: 0, result.score),
                rank = rankingFor(session.slug).myPosition,
                individual = individual(),
                team = if (hasTeam) TeamImpact("Los Tigres", teamTotal(), teamRank(), teamRivals.size + 1) else null
            )
        )
    }
}
