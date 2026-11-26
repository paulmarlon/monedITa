package com.example.authapp

import android.app.Application
import com.example.authapp.data.local.SessionStore
import com.example.authapp.data.remote.ApiClient
import com.example.authapp.data.repository.AuthRepository

class App : Application() {
    val repository: AuthRepository by lazy {
        AuthRepository(ApiClient.service, SessionStore(this))
    }
}
