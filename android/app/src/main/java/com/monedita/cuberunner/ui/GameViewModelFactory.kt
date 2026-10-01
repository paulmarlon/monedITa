package com.monedita.cuberunner.ui

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import com.monedita.cuberunner.data.ApiClient
import com.monedita.cuberunner.data.GameRepository
import com.monedita.cuberunner.data.TokenStore

class GameViewModelFactory(private val context: Context) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        val store = TokenStore(context.applicationContext)
        val repo = GameRepository(ApiClient.create(store), store)
        return GameViewModel(repo) as T
    }
}
