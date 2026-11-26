package com.example.authapp.data.remote

import com.example.authapp.data.model.GuardarPuntajeRequest
import com.example.authapp.data.model.GuardarPuntajeResponse
import com.example.authapp.data.model.IniciarResponse
import com.example.authapp.data.model.LoginRequest
import com.example.authapp.data.model.LoginResponse
import com.example.authapp.data.model.LogoutResponse
import com.example.authapp.data.model.MeResponse
import com.example.authapp.data.model.RankingResponse
import com.example.authapp.data.model.ResendTwoFactorRequest
import com.example.authapp.data.model.ResendTwoFactorResponse
import com.example.authapp.data.model.VerifyTwoFactorRequest
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Path

interface ApiService {

    @POST("login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @POST("2fa/verify")
    suspend fun verifyTwoFactor(@Body body: VerifyTwoFactorRequest): Response<LoginResponse>

    @POST("2fa/resend")
    suspend fun resendTwoFactor(@Body body: ResendTwoFactorRequest): Response<ResendTwoFactorResponse>

    @GET("me")
    suspend fun me(@Header("Authorization") bearer: String): Response<MeResponse>

    @POST("logout")
    suspend fun logout(@Header("Authorization") bearer: String): Response<LogoutResponse>

    // ---- Minijuegos ----
    @POST("juegos/{slug}/iniciar")
    suspend fun iniciarJuego(
        @Path("slug") slug: String,
        @Header("Authorization") bearer: String
    ): Response<IniciarResponse>

    @POST("juegos/guardar-puntaje")
    suspend fun guardarPuntaje(
        @Header("Authorization") bearer: String,
        @Header("Idempotency-Key") idempotencyKey: String,
        @Body body: GuardarPuntajeRequest
    ): Response<GuardarPuntajeResponse>

    @GET("juegos/{slug}/ranking")
    suspend fun ranking(
        @Path("slug") slug: String,
        @Header("Authorization") bearer: String
    ): Response<RankingResponse>
}
