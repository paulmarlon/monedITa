package com.example.authapp

import android.app.Application
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.data.game.FakeGameRepository
import com.example.authapp.data.game.FakeScenario
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.game.RemoteGameRepository
import com.example.authapp.data.local.SessionStore
import com.example.authapp.data.remote.ApiClient
import com.example.authapp.data.repository.AuthRepository
import com.example.authapp.games.taps.TapCounterGame

class App : Application() {
    private val store: SessionStore by lazy { SessionStore(this) }

    val repository: AuthRepository by lazy { AuthRepository(ApiClient.service, store) }

    /** USE_FAKE_BACKEND / FAKE_SCENARIO se cambian en gradle.properties (solo build debug). */
    val gameRepository: GameRepository by lazy {
        if (BuildConfig.USE_FAKE_BACKEND) {
            FakeGameRepository(FakeScenario.fromName(BuildConfig.FAKE_SCENARIO))
        } else {
            RemoteGameRepository(ApiClient.service, store) { repository.onSessionExpired() }
        }
    }

    /** Cada juego nuevo = una línea aquí (+ su fila en la tabla `juegos` con el mismo slug). */
    val gameRegistry: GameRegistry by lazy { GameRegistry(setOf(TapCounterGame)) }
}
