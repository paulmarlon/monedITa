package com.example.authapp.data.game

import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.repository.ApiResult

interface GameRepository {
    /** Success(null) = no hay ciclo activo. */
    suspend fun dashboard(slugs: List<String>): ApiResult<DashboardData?>

    /** Success(null) = el servidor no publica catálogo: la app usa los juegos instalados. */
    suspend fun catalog(): ApiResult<List<GameInfo>?>

    suspend fun startGame(slug: String): ApiResult<StartedGame>
    suspend fun submitScore(session: GameSession, result: GameResult): ApiResult<SubmitResult>
}
