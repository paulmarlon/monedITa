package com.example.authapp.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.game.ScoreEntry
import com.example.authapp.data.game.StartedGame
import com.example.authapp.data.game.SubmitResult
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface PlayState {
    data object Idle : PlayState
    data object Starting : PlayState                                  // pidiendo la partida (consume crédito)
    data class StartFailed(val slug: String, val message: String) : PlayState
    data class Playing(val session: GameSession) : PlayState          // el juego corre
    data object Submitting : PlayState                                // enviando el puntaje

    /** Conserva partida y resultado. possiblySaved = el 403 llegó tras un fallo de red: quizá ya se guardó. */
    data class SubmitFailed(
        val session: GameSession,
        val result: GameResult,
        val message: String,
        val possiblySaved: Boolean = false
    ) : PlayState

    data class Finished(
        val session: GameSession,
        val result: GameResult,
        val outcome: SubmitResult,
        val chipsUsed: Double?,
        val creditsLeft: Double?
    ) : PlayState
}

private const val POSSIBLY_SAVED =
    "Tu puntaje podría haberse guardado ya (la conexión falló en el primer intento). " +
        "Revisa el ranking antes de volver a jugar."

/** Host de partidas: único que habla con el servidor. Los juegos no lo conocen. */
class PlayViewModel(private val repo: GameRepository) : ViewModel() {

    private val _state = MutableStateFlow<PlayState>(PlayState.Idle)
    val state: StateFlow<PlayState> = _state.asStateFlow()

    /** Créditos tras iniciar una partida (null = aún no cambió; se usa el valor del tablero). */
    private val _credits = MutableStateFlow<Double?>(null)
    val credits: StateFlow<Double?> = _credits.asStateFlow()

    /** Partidas de esta sesión de la app, para "Actividad reciente". */
    private val _recent = MutableStateFlow<List<ScoreEntry>>(emptyList())
    val recent: StateFlow<List<ScoreEntry>> = _recent.asStateFlow()

    private var started: StartedGame? = null
    private var uncertainSubmit = false // hubo un fallo de red al enviar: el servidor pudo haber guardado
    private var nextId = 1L

    fun start(slug: String) {
        uncertainSubmit = false
        _state.value = PlayState.Starting // síncrono: la pantalla nunca ve un estado viejo
        viewModelScope.launch {
            _state.value = when (val r = repo.startGame(slug)) {
                is ApiResult.Success -> {
                    started = r.data
                    r.data.creditsLeft?.let { _credits.value = it }
                    PlayState.Playing(r.data.session)
                }
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
        if (failed.possiblySaved) return // reenviar solo daría 403 otra vez
        submit(failed.session, failed.result)
    }

    private fun submit(session: GameSession, result: GameResult) {
        _state.value = PlayState.Submitting
        viewModelScope.launch {
            _state.value = when (val r = repo.submitScore(session, result)) {
                is ApiResult.Success -> {
                    val entry = ScoreEntry(nextId++, session.slug, r.data.score, System.currentTimeMillis(), r.data.isPersonalBest)
                    _recent.value = (listOf(entry) + _recent.value).take(10)
                    PlayState.Finished(session, result, r.data, started?.chipsUsed, _credits.value)
                }
                is ApiResult.Unauthorized -> PlayState.SubmitFailed(session, result, "Tu sesión expiró.")
                is ApiResult.Error -> {
                    if (r.network) uncertainSubmit = true
                    if (r.code == 403 && uncertainSubmit) {
                        PlayState.SubmitFailed(session, result, POSSIBLY_SAVED, possiblySaved = true)
                    } else {
                        PlayState.SubmitFailed(session, result, r.message)
                    }
                }
            }
        }
    }
}
