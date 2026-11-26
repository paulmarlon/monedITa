package com.example.authapp

import android.app.Application
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.data.game.FakeGameRepository
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.local.SessionStore
import com.example.authapp.data.remote.ApiClient
import com.example.authapp.data.repository.AuthRepository
import com.example.authapp.games.taps.TapCounterGame

class App : Application() {
    val repository: AuthRepository by lazy {
        AuthRepository(ApiClient.service, SessionStore(this))
    }

    /** Hoy: datos de prueba en memoria. Luego: RemoteGameRepository (Retrofit). */
    val gameRepository: GameRepository by lazy { FakeGameRepository() }

    /** Cada juego nuevo = una línea aquí. El tablero no cambia. */
    val gameRegistry: GameRegistry by lazy { GameRegistry(setOf(TapCounterGame)) }
}
