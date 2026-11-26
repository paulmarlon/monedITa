package com.example.authapp.data.model

import com.google.gson.annotations.SerializedName

// Todos los campos tienen valor por defecto (=> Kotlin genera constructor sin args y Gson
// respeta los defaults) y son null-safe: si falta token / requires_2fa nunca hay crash.

data class LoginRequest(
    @SerializedName("registro_universitario") val registroUniversitario: String,
    val password: String,
    @SerializedName("device_id") val deviceId: String,
    @SerializedName("device_name") val deviceName: String
)

data class VerifyTwoFactorRequest(
    @SerializedName("challenge_id") val challengeId: String,
    val code: String,
    @SerializedName("device_id") val deviceId: String,
    @SerializedName("device_name") val deviceName: String
)

data class ResendTwoFactorRequest(
    @SerializedName("challenge_id") val challengeId: String,
    @SerializedName("device_id") val deviceId: String
)

data class UserDto(
    val id: Long = 0,
    val name: String = "",
    val alias: String? = null,
    val avatar: String? = null,
    @SerializedName("team_id") val teamId: Long? = null,
    @SerializedName("team_name") val teamName: String? = null   // opcional: el backend aún puede no enviarlo
)

/** Sirve para /login y /2fa/verify. */
data class LoginResponse(
    val success: Boolean = false,
    @SerializedName("requires_2fa") val requires2fa: Boolean = false,
    @SerializedName("challenge_id") val challengeId: String? = null,
    @SerializedName("email_masked") val emailMasked: String? = null,
    @SerializedName("expires_in") val expiresIn: Int? = null,
    val message: String? = null,
    val token: String? = null,
    @SerializedName("token_type") val tokenType: String? = null,
    val device: String? = null,
    val user: UserDto? = null,
    @SerializedName("saldo_actual") val saldoActual: Double? = null
)

data class ResendTwoFactorResponse(
    val success: Boolean = false,
    @SerializedName("challenge_id") val challengeId: String? = null,
    @SerializedName("expires_in") val expiresIn: Int? = null,
    val message: String? = null
)

data class MeResponse(
    val success: Boolean = false,
    val user: UserDto? = null,
    @SerializedName("saldo_actual") val saldoActual: Double? = null
)

data class LogoutResponse(
    val success: Boolean = false,
    val message: String? = null
)

/** Cuerpo de error: {"success":false,"message":"..."} o el 422 estándar de Laravel con "errors". */
data class ApiErrorResponse(
    val success: Boolean = false,
    val message: String? = null,
    val errors: Map<String, List<String>>? = null
)

/** Modelo de dominio que consume la UI. */
data class Profile(
    val id: Long,
    val name: String,
    val alias: String?,
    val teamId: Long?,
    val teamName: String? = null,
    val saldoActual: Double,
    val deviceLabel: String
) {
    /** Texto del equipo para la UI: nombre si existe; si solo hay ID, "Equipo #id"; si no, "Sin equipo". */
    val teamLabel: String
        get() = teamName?.trim()?.takeIf { it.isNotEmpty() }
            ?: teamId?.let { "Equipo #$it" }
            ?: "Sin equipo"
}

// ---------------- Minijuegos (contrato real de MinijuegoApiController / RankingApiController) ----------------

data class IniciarResponse(
    val success: Boolean = false,
    val message: String? = null,
    @SerializedName("partida_id") val partidaId: String? = null,
    @SerializedName("fichas_usadas") val fichasUsadas: Double? = null,
    @SerializedName("nuevo_saldo") val nuevoSaldo: Double? = null
)

data class GuardarPuntajeRequest(
    val slug: String,
    val puntaje: Int,
    @SerializedName("partida_id") val partidaId: String
)

data class GuardarPuntajeResponse(
    val success: Boolean = false,
    val message: String? = null,
    val puntaje: Int? = null,
    @SerializedName("mejor_puntaje") val mejorPuntaje: Int? = null,
    @SerializedName("es_record") val esRecord: Boolean? = null
)

data class RankingEntryDto(
    val posicion: Int? = null,
    val alias: String? = null,
    val avatar: String? = null,
    val puntaje: Int? = null,
    @SerializedName("soy_yo") val soyYo: Boolean? = null
)

data class RankingResponse(
    val success: Boolean = false,
    val juego: String? = null,
    val top: List<RankingEntryDto>? = null,
    @SerializedName("mi_posicion") val miPosicion: Int? = null,
    @SerializedName("mi_mejor") val miMejor: Int? = null
)
