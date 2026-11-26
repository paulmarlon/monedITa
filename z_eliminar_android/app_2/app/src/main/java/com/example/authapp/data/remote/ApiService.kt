package com.example.authapp.data.remote

import com.example.authapp.data.model.LoginRequest
import com.example.authapp.data.model.LoginResponse
import com.example.authapp.data.model.LogoutResponse
import com.example.authapp.data.model.MeResponse
import com.example.authapp.data.model.ResendTwoFactorRequest
import com.example.authapp.data.model.ResendTwoFactorResponse
import com.example.authapp.data.model.VerifyTwoFactorRequest
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

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
}
