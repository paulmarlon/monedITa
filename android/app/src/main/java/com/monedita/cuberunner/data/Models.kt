package com.monedita.cuberunner.data

data class LoginRequest(
    val registro_universitario: String,
    val password: String,
    val device_name: String = "android"
)

data class UserDto(
    val id: Int,
    val name: String,
    val alias: String,
    val avatar: String?,
    val team_id: Int?
)

data class LoginResponse(
    val success: Boolean,
    val token: String?,
    val user: UserDto?,
    val saldo_actual: Double?
)

data class MeResponse(
    val success: Boolean,
    val user: UserDto?,
    val saldo_actual: Double?
)

data class IniciarResponse(
    val success: Boolean,
    val partida_id: String?,
    val fichas_usadas: Double?,
    val nuevo_saldo: Double?
)

data class PuntajeRequest(
    val slug: String,
    val puntaje: Int,
    val partida_id: String
)

data class PuntajeResponse(
    val success: Boolean,
    val puntaje: Int?,
    val mejor_puntaje: Int?,
    val es_record: Boolean?
)

data class ApiError(val success: Boolean?, val message: String?)
