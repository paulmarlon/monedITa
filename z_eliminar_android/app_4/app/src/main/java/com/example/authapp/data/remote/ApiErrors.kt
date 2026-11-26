package com.example.authapp.data.remote

import com.example.authapp.data.model.ApiErrorResponse
import com.google.gson.Gson
import com.google.gson.JsonParseException
import retrofit2.Response
import java.io.IOException

private val errorGson = Gson()

/** Lee errorBody() y devuelve el `message` del servidor (o el primer error de validación, o `default`). */
fun Response<*>.apiErrorMessage(default: String): String {
    val raw = try {
        errorBody()?.string()
    } catch (_: IOException) {
        null
    }
    if (raw.isNullOrBlank()) return default
    return try {
        val e = errorGson.fromJson(raw, ApiErrorResponse::class.java)
        e?.message?.takeIf { it.isNotBlank() }
            ?: e?.errors?.values?.firstOrNull()?.firstOrNull()
            ?: default
    } catch (_: JsonParseException) {
        default
    }
}
