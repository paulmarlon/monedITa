package com.example.authapp.data.repository

import android.os.Build
import com.example.authapp.data.local.SessionStore
import com.example.authapp.data.model.ApiErrorResponse
import com.example.authapp.data.model.LoginRequest
import com.example.authapp.data.model.LoginResponse
import com.example.authapp.data.model.Profile
import com.example.authapp.data.model.ResendTwoFactorRequest
import com.example.authapp.data.model.UserDto
import com.example.authapp.data.model.VerifyTwoFactorRequest
import com.example.authapp.data.remote.ApiService
import com.google.gson.Gson
import com.google.gson.JsonParseException
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import retrofit2.Response
import java.io.IOException

sealed interface ApiResult<out T> {
    data class Success<T>(val data: T) : ApiResult<T>
    data object Unauthorized : ApiResult<Nothing>
    data class Error(val message: String, val code: Int? = null, val network: Boolean = false) : ApiResult<Nothing>
}

/** Desafío 2FA: vive solo en memoria (UiState), nunca en DataStore. */
data class TwoFactorChallenge(
    val challengeId: String,
    val emailMasked: String?,
    val expiresIn: Int,
    val message: String?
)

data class ResendInfo(val challengeId: String, val expiresIn: Int, val message: String?)

sealed interface LoginOutcome {
    data class Authenticated(val profile: Profile) : LoginOutcome
    data class RequiresTwoFactor(val challenge: TwoFactorChallenge) : LoginOutcome
    data class Failure(val message: String) : LoginOutcome
}

class AuthRepository(
    private val api: ApiService,
    private val store: SessionStore
) {
    private val gson = Gson()

    private val _sessionExpired = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    /** Se emite cuando cualquier llamada autenticada recibe 401 (p. ej. sesión abierta en otro dispositivo). */
    val sessionExpired: SharedFlow<Unit> = _sessionExpired.asSharedFlow()

    suspend fun onSessionExpired() {
        store.clearSession()
        _sessionExpired.tryEmit(Unit)
    }

    suspend fun hasToken(): Boolean = !store.getToken().isNullOrBlank()

    // ---------------------------------------------------------------- login
    suspend fun login(registro: String, password: String): LoginOutcome =
        safeCall({ LoginOutcome.Failure(it) }) {
            val resp = api.login(
                LoginRequest(
                    registroUniversitario = registro.trim(),
                    password = password,
                    deviceId = store.getOrCreateDeviceId(),
                    deviceName = Build.MODEL
                )
            )
            if (!resp.isSuccessful) {
                val fallback = when (resp.code()) {
                    401 -> "Credenciales incorrectas."
                    422 -> "Datos inválidos. Revisa tu registro y contraseña."
                    429 -> "Demasiados intentos. Intenta más tarde."
                    else -> "Error del servidor (${resp.code()})."
                }
                return@safeCall LoginOutcome.Failure(resp.errorMessage(fallback))
            }

            val body = resp.body()
            if (body == null || !body.success) {
                return@safeCall LoginOutcome.Failure(body?.message ?: "Respuesta inválida del servidor.")
            }

            if (body.requires2fa) {
                // ⚠️ 2FA pendiente: NO se toca DataStore, aunque el body trajera un token.
                val id = body.challengeId
                return@safeCall if (id.isNullOrBlank()) {
                    LoginOutcome.Failure("Respuesta inválida: falta challenge_id.")
                } else {
                    LoginOutcome.RequiresTwoFactor(
                        TwoFactorChallenge(id, body.emailMasked, body.expiresIn ?: DEFAULT_EXPIRES, body.message)
                    )
                }
            }

            val profile = persistIfAuthenticated(body)
            if (profile != null) LoginOutcome.Authenticated(profile)
            else LoginOutcome.Failure("Respuesta inválida: falta el token.")
        }

    // ------------------------------------------------------------ 2fa/verify
    suspend fun verifyTwoFactor(challengeId: String, code: String): ApiResult<Profile> =
        safeCall({ ApiResult.Error(it) }) {
            val resp = api.verifyTwoFactor(
                VerifyTwoFactorRequest(
                    challengeId = challengeId,
                    code = code,
                    deviceId = store.getOrCreateDeviceId(),
                    deviceName = Build.MODEL
                )
            )
            if (!resp.isSuccessful) {
                // 403 / 422 / 429: mensaje real del servidor, sin guardar token
                return@safeCall ApiResult.Error(
                    resp.errorMessage("No se pudo verificar el código (${resp.code()}).")
                )
            }
            val body = resp.body()
            val profile = persistIfAuthenticated(body)
            if (profile != null) ApiResult.Success(profile)
            else ApiResult.Error(body?.message ?: "Respuesta inválida del servidor.")
        }

    // ------------------------------------------------------------ 2fa/resend
    suspend fun resendTwoFactor(challengeId: String): ApiResult<ResendInfo> =
        safeCall({ ApiResult.Error(it) }) {
            val resp = api.resendTwoFactor(
                ResendTwoFactorRequest(challengeId, store.getOrCreateDeviceId())
            )
            if (!resp.isSuccessful) {
                return@safeCall ApiResult.Error(
                    resp.errorMessage("No se pudo reenviar el código (${resp.code()}).")
                )
            }
            val body = resp.body()
            if (body == null || !body.success) {
                return@safeCall ApiResult.Error(body?.message ?: "Respuesta inválida del servidor.")
            }
            ApiResult.Success(
                ResendInfo(
                    challengeId = body.challengeId?.takeIf { it.isNotBlank() } ?: challengeId,
                    expiresIn = body.expiresIn ?: DEFAULT_EXPIRES,
                    message = body.message
                )
            )
        }

    // -------------------------------------------------------------------- me
    /** Valida el token guardado con GET /me. Si da 401 borra el token local. */
    suspend fun fetchProfile(): ApiResult<Profile> = safeCall({ ApiResult.Error(it) }) {
        val token = store.getToken()
        if (token.isNullOrBlank()) return@safeCall ApiResult.Unauthorized

        val resp = api.me("Bearer $token")
        when {
            resp.isSuccessful -> {
                val body = resp.body()
                val user = body?.user
                if (user == null) {
                    ApiResult.Error("Respuesta inválida del servidor.")
                } else {
                    val label = store.getDeviceLabel() ?: Build.MODEL
                    ApiResult.Success(user.toProfile(body.saldoActual, label))
                }
            }
            resp.code() == 401 -> {
                store.clearSession()
                ApiResult.Unauthorized
            }
            else -> ApiResult.Error("Error del servidor (${resp.code()}).")
        }
    }

    // ---------------------------------------------------------------- logout
    /** Llama a POST /logout y SIEMPRE limpia la sesión local. */
    suspend fun logout() {
        try {
            val token = store.getToken()
            if (!token.isNullOrBlank()) api.logout("Bearer $token")
        } catch (e: CancellationException) {
            throw e
        } catch (_: Exception) {
            // sin red u otro fallo: igual limpiamos localmente
        } finally {
            store.clearSession()
        }
    }

    // ------------------------------------------------------------- helpers
    /**
     * ÚNICO punto del proyecto que escribe el token en DataStore.
     * Solo persiste si success == true, requires_2fa == false y token no vacío.
     */
    private suspend fun persistIfAuthenticated(body: LoginResponse?): Profile? {
        if (body == null || !body.success || body.requires2fa) return null
        val token = body.token
        val user = body.user
        if (token.isNullOrBlank() || user == null) return null
        val label = body.device ?: Build.MODEL
        store.saveSession(token, label)
        return user.toProfile(body.saldoActual, label)
    }

    private fun UserDto.toProfile(saldo: Double?, deviceLabel: String) = Profile(
        id = id,
        name = name,
        alias = alias,
        teamId = teamId,
        teamName = teamName,
        saldoActual = saldo ?: 0.0,
        deviceLabel = deviceLabel
    )

    /** Lee errorBody() y devuelve el `message` real del servidor (o el primer error de validación). */
    private fun Response<*>.errorMessage(default: String): String {
        val raw = try {
            errorBody()?.string()
        } catch (_: IOException) {
            null
        }
        if (raw.isNullOrBlank()) return default
        return try {
            val e = gson.fromJson(raw, ApiErrorResponse::class.java)
            e?.message?.takeIf { it.isNotBlank() }
                ?: e?.errors?.values?.firstOrNull()?.firstOrNull()
                ?: default
        } catch (_: JsonParseException) {
            default
        }
    }

    private inline fun <T> safeCall(onError: (String) -> T, block: () -> T): T =
        try {
            block()
        } catch (e: CancellationException) {
            throw e
        } catch (e: IOException) {
            onError("No se pudo conectar con el servidor. Revisa tu conexión.")
        } catch (e: Exception) {
            onError("Error inesperado: ${e.localizedMessage ?: "desconocido"}")
        }

    private companion object {
        const val DEFAULT_EXPIRES = 600
    }
}
