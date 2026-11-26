package com.example.authapp.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.game.SubmitResult
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface PlayState {
    data object Idle : PlayState
    data object Starting : PlayState                                  // pidiendo sesión al servidor
    data class StartFailed(val slug: String, val message: String) : PlayState
    data class Playing(val session: GameSession) : PlayState          // el juego corre
    data object Submitting : PlayState                                // enviando puntaje
    data class SubmitFailed(val session: GameSession, val result: GameResult, val message: String) : PlayState
    data class Finished(val session: GameSession, val result: GameResult, val outcome: SubmitResult) : PlayState
}

/** Host de partidas: único que habla con el servidor. Los juegos no lo conocen. */
class PlayViewModel(private val repo: GameRepository) : ViewModel() {

    private val _state = MutableStateFlow<PlayState>(PlayState.Idle)
    val state: StateFlow<PlayState> = _state.asStateFlow()

    fun start(slug: String) {
        _state.value = PlayState.Starting // síncrono: la pantalla nunca ve un estado viejo
        viewModelScope.launch {
            _state.value = when (val r = repo.createSession(slug)) {
                is ApiResult.Success -> PlayState.Playing(r.data)
                is ApiResult.Unauthorized -> PlayState.StartFailed(slug, "Tu sesión expiró.")
                is ApiResult.Error -> PlayState.StartFailed(slug, r.message)
            }
        }
    }

    fun onGameFinished(result: GameResult) {
        val playing = _state.value as? PlayState.Playing ?: return
        submit(playing.session, result)
    }

    fun retrySubmit() {
        val failed = _state.value as? PlayState.SubmitFailed ?: return
        submit(failed.session, failed.result)
    }

    private fun submit(session: GameSession, result: GameResult) {
        _state.value = PlayState.Submitting
        viewModelScope.launch {
            _state.value = when (val r = repo.submitScore(session, result)) {
                is ApiResult.Success -> PlayState.Finished(session, result, r.data)
                is ApiResult.Unauthorized -> PlayState.SubmitFailed(session, result, "Tu sesión expiró.")
                is ApiResult.Error -> PlayState.SubmitFailed(session, result, r.message)
            }
        }
    }
}
