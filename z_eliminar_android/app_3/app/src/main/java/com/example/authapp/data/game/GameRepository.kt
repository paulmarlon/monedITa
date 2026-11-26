package com.example.authapp.data.game

import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.repository.ApiResult

interface GameRepository {
    /** Success(null) = no hay ciclo activo. */
    suspend fun dashboard(): ApiResult<DashboardData?>
    suspend fun games(): ApiResult<List<GameInfo>>
    suspend fun createSession(slug: String): ApiResult<GameSession>
    suspend fun submitScore(session: GameSession, result: GameResult): ApiResult<SubmitResult>
}
