package com.monedita.cuberunner.data

import com.google.gson.Gson
import retrofit2.Response
import java.io.IOException

sealed class ApiResult<out T> {
    data class Ok<T>(val data: T) : ApiResult<T>()
    data class Fail(val code: Int, val message: String) : ApiResult<Nothing>()
}

class GameRepository(private val api: GameApi, private val store: TokenStore) {

    private suspend fun <T> call(block: suspend () -> Response<T>): ApiResult<T> = try {
        val res = block()
        val body = res.body()
        if (res.isSuccessful && body != null) {
            ApiResult.Ok(body)
        } else {
            val msg = runCatching {
                Gson().fromJson(res.errorBody()?.string(), ApiError::class.java)?.message
            }.getOrNull()
            ApiResult.Fail(res.code(), msg ?: "Error ${res.code()}")
        }
    } catch (e: IOException) {
        ApiResult.Fail(0, "Sin conexion con el servidor (${e.javaClass.simpleName})")
    }

    fun haySesion(): Boolean = store.token != null

    fun cerrarSesionLocal() = store.clear()

    suspend fun login(registro: String, password: String): ApiResult<LoginResponse> {
        val r = call { api.login(LoginRequest(registro, password, "android")) }
        if (r is ApiResult.Ok) store.token = r.data.token
        return r
    }

    suspend fun me(): ApiResult<MeResponse> = call { api.me() }

    suspend fun iniciar(slug: String): ApiResult<IniciarResponse> {
        val r = call { api.iniciar(slug) }
        if (r is ApiResult.Ok) store.partidaId = r.data.partida_id
        return r
    }

    suspend fun guardarPuntaje(slug: String, puntaje: Int): ApiResult<PuntajeResponse> {
        val id = store.partidaId ?: return ApiResult.Fail(0, "No hay partida activa")
        val r = call { api.guardarPuntaje(PuntajeRequest(slug, puntaje, id)) }
        // Solo se libera la partida si el servidor la acepto
        if (r is ApiResult.Ok) store.partidaId = null
        return r
    }
}
