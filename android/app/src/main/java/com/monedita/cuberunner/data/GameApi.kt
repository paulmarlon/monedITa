package com.monedita.cuberunner.data

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

interface GameApi {
    @POST("login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @GET("me")
    suspend fun me(): Response<MeResponse>

    @POST("juegos/{slug}/iniciar")
    suspend fun iniciar(@Path("slug") slug: String): Response<IniciarResponse>

    @POST("juegos/guardar-puntaje")
    suspend fun guardarPuntaje(@Body body: PuntajeRequest): Response<PuntajeResponse>
}
