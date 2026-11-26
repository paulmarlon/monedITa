package com.example.authapp.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.authapp.data.model.Profile
import com.example.authapp.data.repository.ApiResult
import com.example.authapp.data.repository.AuthRepository
import com.example.authapp.data.repository.LoginOutcome
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface SessionState {
    /** Loading inicial: validando el token guardado con GET /me. */
    data object Checking : SessionState

    /** Idle: pantalla de login. `busy` = Loading del login; `error` = mensaje a mostrar. */
    data class Idle(val error: String? = null, val busy: Boolean = false) : SessionState

    /** Pantalla de código de 6 dígitos. Vive solo en memoria; NO hay token todavía. */
    data class RequiresTwoFactor(
        val challengeId: String,
        val emailMasked: String?,
        val expiresIn: Int,
        val message: String?,
        val busy: Boolean = false,
        val error: String? = null,
        val info: String? = null
    ) : SessionState

    /** Solo se alcanza DESPUÉS de que el repositorio guardó el token en DataStore. */
    data class Authenticated(
        val profile: Profile,
        val busy: Boolean = false,
        val loginKey: String = java.util.UUID.randomUUID().toString()
    ) : SessionState

    /** Fallo de red al validar el token guardado (no es 401): permite reintentar. */
    data class Error(val message: String) : SessionState
}

class SessionViewModel(private val repo: AuthRepository) : ViewModel() {

    private val _state = MutableStateFlow<SessionState>(SessionState.Checking)
    val state: StateFlow<SessionState> = _state.asStateFlow()

    init {
        bootstrap()
    }

    fun bootstrap() {
        viewModelScope.launch {
            _state.value = SessionState.Checking
            if (!repo.hasToken()) {
                _state.value = SessionState.Idle()
                return@launch
            }
            _state.value = when (val r = repo.fetchProfile()) {
                is ApiResult.Success -> SessionState.Authenticated(r.data)
                // 401: el repo ya borró el token (p. ej. sesión abierta en otro dispositivo)
                is ApiResult.Unauthorized ->
                    SessionState.Idle("Tu sesión ya no es válida. Inicia sesión nuevamente.")
                is ApiResult.Error -> SessionState.Error(r.message)
            }
        }
    }

    fun login(registro: String, password: String) {
        if (registro.isBlank() || password.isBlank()) {
            _state.value = SessionState.Idle("Completa todos los campos.")
            return
        }
        viewModelScope.launch {
            _state.value = SessionState.Idle(busy = true)
            _state.value = when (val r = repo.login(registro, password)) {
                is LoginOutcome.Authenticated -> SessionState.Authenticated(r.profile)
                is LoginOutcome.RequiresTwoFactor -> SessionState.RequiresTwoFactor(
                    challengeId = r.challenge.challengeId,
                    emailMasked = r.challenge.emailMasked,
                    expiresIn = r.challenge.expiresIn,
                    message = r.challenge.message
                )
                is LoginOutcome.Failure -> SessionState.Idle(r.message)
            }
        }
    }

    fun verifyCode(code: String) {
        val current = _state.value as? SessionState.RequiresTwoFactor ?: return
        if (current.busy) return
        if (code.length != 6 || !code.all { it.isDigit() }) {
            _state.value = current.copy(error = "Ingresa los 6 dígitos del código.", info = null)
            return
        }
        viewModelScope.launch {
            _state.value = current.copy(busy = true, error = null, info = null)
            _state.value = when (val r = repo.verifyTwoFactor(current.challengeId, code)) {
                is ApiResult.Success -> SessionState.Authenticated(r.data)
                is ApiResult.Error -> current.copy(busy = false, error = r.message, info = null)
                is ApiResult.Unauthorized -> current.copy(busy = false, error = "No autorizado.", info = null)
            }
        }
    }

    fun resendCode() {
        val current = _state.value as? SessionState.RequiresTwoFactor ?: return
        if (current.busy) return
        viewModelScope.launch {
            _state.value = current.copy(busy = true, error = null, info = null)
            _state.value = when (val r = repo.resendTwoFactor(current.challengeId)) {
                is ApiResult.Success -> current.copy(
                    challengeId = r.data.challengeId,
                    expiresIn = r.data.expiresIn,
                    busy = false,
                    error = null,
                    info = r.data.message ?: "Te enviamos un nuevo código."
                )
                is ApiResult.Error -> current.copy(busy = false, error = r.message, info = null)
                is ApiResult.Unauthorized -> current.copy(busy = false, error = "No autorizado.", info = null)
            }
        }
    }

    /** Descarta el desafío en memoria y vuelve al login. No hay nada que limpiar en DataStore. */
    fun backToLogin() {
        _state.value = SessionState.Idle()
    }

    fun logout() {
        val current = _state.value
        if (current is SessionState.Authenticated) {
            _state.value = current.copy(busy = true)
        }
        viewModelScope.launch {
            repo.logout() // siempre limpia el token local
            _state.value = SessionState.Idle()
        }
    }

    /** Para la pantalla de error de red: salir sin depender del servidor. */
    fun clearLocalSession() = logout()
}

class SessionViewModelFactory(private val repo: AuthRepository) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T = SessionViewModel(repo) as T
}
