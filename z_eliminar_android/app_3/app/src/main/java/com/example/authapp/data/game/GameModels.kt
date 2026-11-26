package com.example.authapp.data.game

data class Cycle(val id: Long, val name: String, val daysLeft: Int)

data class IndividualScore(
    val total: Int, val best: Int, val gamesPlayed: Int,
    val rank: Int?, val totalPlayers: Int?
)

data class TeamScore(
    val teamId: Long, val name: String, val total: Int,
    val myContribution: Int, val rank: Int?, val totalTeams: Int?
)

data class GameScoreSummary(val slug: String, val name: String, val bestScore: Int, val plays: Int)

data class ScoreEntry(
    val id: Long, val gameSlug: String, val gameName: String,
    val score: Int, val playedAtMillis: Long, val isPersonalBest: Boolean
)

/** team == null -> el usuario aún no tiene equipo en este ciclo. */
data class DashboardData(
    val cycle: Cycle,
    val individual: IndividualScore,
    val team: TeamScore?,
    val games: List<GameScoreSummary>,
    val recent: List<ScoreEntry>
)

enum class GameStatus { AVAILABLE, COMING_SOON, MAINTENANCE }

data class GameInfo(
    val slug: String, val name: String, val description: String,
    val status: GameStatus, val myBestScore: Int?
)

data class TeamImpact(val name: String, val teamTotal: Int, val rank: Int?, val totalTeams: Int?)

data class SubmitResult(
    val score: Int, val isPersonalBest: Boolean, val bestScore: Int,
    val rank: Int?, val totalPlayers: Int?, val team: TeamImpact?
)
