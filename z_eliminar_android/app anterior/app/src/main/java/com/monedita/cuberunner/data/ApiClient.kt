package com.monedita.cuberunner.data

import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    // La barra final es obligatoria.
    private const val BASE_URL = "https://tapita.onrender.com/api/"

    fun create(store: TokenStore): GameApi {
        val client = OkHttpClient.Builder()
            // El plan gratuito de Render tarda en despertar: damos margen
            .connectTimeout(60, TimeUnit.SECONDS)
            .readTimeout(60, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .addInterceptor { chain ->
                val req = chain.request().newBuilder()
                    .header("Accept", "application/json")
                    .apply { store.token?.let { header("Authorization", "Bearer $it") } }
                    .build()
                chain.proceed(req)
            }
            .addInterceptor(HttpLoggingInterceptor().apply {
                // BASIC no imprime cabeceras, asi no se filtra el token
                level = HttpLoggingInterceptor.Level.BASIC
            })
            .build()

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(GameApi::class.java)
    }
}
