package com.example.authapp.data.game

import com.example.authapp.core.game.GameSession

// Todo lo relativo a ciclo, créditos, puntaje individual y equipo es OPCIONAL (null = el backend no lo envía).
// La UI solo muestra lo que no es null.

data class Cycle(val id: Long?, val name: String, val daysLeft: Int?)

data class IndividualScore(
    val total: Int, val best: Int?, val gamesPlayed: Int?,
    val rank: Int?, val totalPlayers: Int?
)

data class TeamScore(
    val teamId: Long?, val name: String, val total: Int,
    val myContribution: Int?, val rank: Int?, val totalTeams: Int?
)

data class RankEntry(
    val position: Int, val alias: String, val avatarUrl: String?,
    val score: Int, val isMe: Boolean
)

/** Ranking de un juego: top + tu mejor puntaje y posición. */
data class GameRanking(
    val slug: String, val title: String,
    val top: List<RankEntry>, val myPosition: Int?, val myBest: Int?
)

data class DashboardData(
    val cycle: Cycle? = null,
    val credits: Double? = null,
    val individual: IndividualScore? = null,
    val team: TeamScore? = null,
    val games: List<GameRanking> = emptyList()
)

/** Partida jugada en esta sesión de la app (el backend aún no expone historial). */
data class ScoreEntry(
    val id: Long, val gameSlug: String, val score: Int,
    val playedAtMillis: Long, val isPersonalBest: Boolean
)

enum class GameStatus { AVAILABLE, COMING_SOON, MAINTENANCE }

/** Catálogo publicado por el servidor (si existe). cost = null -> se desconoce. */
data class GameInfo(
    val slug: String, val title: String, val description: String,
    val cost: Double?, val status: GameStatus
)

data class StartedGame(val session: GameSession, val chipsUsed: Double?, val creditsLeft: Double?)

data class TeamImpact(val name: String, val teamTotal: Int, val rank: Int?, val totalTeams: Int?)

data class SubmitResult(
    val score: Int, val isPersonalBest: Boolean, val bestScore: Int, val rank: Int?,
    val individual: IndividualScore? = null,
    val team: TeamImpact? = null
)
