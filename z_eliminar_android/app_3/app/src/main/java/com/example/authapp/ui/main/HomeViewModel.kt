package com.example.authapp.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.authapp.data.game.DashboardData
import com.example.authapp.data.game.GameInfo
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface DashboardUiState {
    data object Loading : DashboardUiState
    data class Content(val data: DashboardData, val refreshing: Boolean = false) : DashboardUiState
    data object NoActiveCycle : DashboardUiState
    data class Error(val message: String) : DashboardUiState
}

sealed interface CatalogUiState {
    data object Loading : CatalogUiState
    data class Content(val games: List<GameInfo>) : CatalogUiState
    data class Error(val message: String) : CatalogUiState
}

private const val EXPIRED = "Tu sesión expiró. Cierra sesión e ingresa de nuevo."

class HomeViewModel(private val repo: GameRepository) : ViewModel() {

    private val _dashboard = MutableStateFlow<DashboardUiState>(DashboardUiState.Loading)
    val dashboard: StateFlow<DashboardUiState> = _dashboard.asStateFlow()

    private val _catalog = MutableStateFlow<CatalogUiState>(CatalogUiState.Loading)
    val catalog: StateFlow<CatalogUiState> = _catalog.asStateFlow()

    init { refresh() }

    fun refresh() {
        loadDashboard()
        loadCatalog()
    }

    private fun loadDashboard() {
        viewModelScope.launch {
            val prev = _dashboard.value
            _dashboard.value =
                if (prev is DashboardUiState.Content) prev.copy(refreshing = true)
                else DashboardUiState.Loading
            _dashboard.value = when (val r = repo.dashboard()) {
                is ApiResult.Success ->
                    r.data?.let { DashboardUiState.Content(it) } ?: DashboardUiState.NoActiveCycle
                is ApiResult.Unauthorized -> DashboardUiState.Error(EXPIRED)
                is ApiResult.Error -> DashboardUiState.Error(r.message)
            }
        }
    }

    private fun loadCatalog() {
        viewModelScope.launch {
            if (_catalog.value !is CatalogUiState.Content) _catalog.value = CatalogUiState.Loading
            _catalog.value = when (val r = repo.games()) {
                is ApiResult.Success -> CatalogUiState.Content(r.data)
                is ApiResult.Unauthorized -> CatalogUiState.Error(EXPIRED)
                is ApiResult.Error -> CatalogUiState.Error(r.message)
            }
        }
    }
}

class PostLoginVmFactory(private val repo: GameRepository) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T = when {
        modelClass.isAssignableFrom(HomeViewModel::class.java) -> HomeViewModel(repo)
        modelClass.isAssignableFrom(PlayViewModel::class.java) -> PlayViewModel(repo)
        else -> throw IllegalArgumentException("ViewModel desconocido: ${modelClass.name}")
    } as T
}
