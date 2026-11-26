package com.example.authapp.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.data.game.DashboardData
import com.example.authapp.data.game.GameInfo
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.game.GameStatus
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

/** Elemento del catálogo ya resuelto: jugable o no, con la etiqueta que explica por qué no. */
data class CatalogItem(val info: GameInfo, val playable: Boolean, val badge: String?)

class HomeViewModel(
    private val repo: GameRepository,
    private val registry: GameRegistry
) : ViewModel() {

    private val _dashboard = MutableStateFlow<DashboardUiState>(DashboardUiState.Loading)
    val dashboard: StateFlow<DashboardUiState> = _dashboard.asStateFlow()

    /** Arranca con los juegos instalados; si el servidor publica catálogo, se reemplaza. */
    private val _catalog = MutableStateFlow(localCatalog())
    val catalog: StateFlow<List<CatalogItem>> = _catalog.asStateFlow()

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
            _dashboard.value = when (val r = repo.dashboard(registry.slugs())) {
                is ApiResult.Success -> r.data?.let { DashboardUiState.Content(it) } ?: DashboardUiState.NoActiveCycle
                is ApiResult.Unauthorized -> DashboardUiState.Error("Tu sesión expiró.")
                is ApiResult.Error -> DashboardUiState.Error(r.message)
            }
        }
    }

    private fun loadCatalog() {
        viewModelScope.launch {
            val r = repo.catalog()
            if (r is ApiResult.Success && r.data != null) {
                _catalog.value = r.data.map { resolve(it) }
            } // en cualquier otro caso se conserva el catálogo local
        }
    }

    private fun localCatalog(): List<CatalogItem> = registry.all().map {
        CatalogItem(GameInfo(it.slug, it.title, it.description, null, GameStatus.AVAILABLE), playable = true, badge = null)
    }

    private fun resolve(info: GameInfo): CatalogItem {
        val supported = registry.find(info.slug) != null
        val badge = when {
            info.status == GameStatus.COMING_SOON -> "Próximamente"
            info.status == GameStatus.MAINTENANCE -> "En mantenimiento"
            !supported -> "Actualiza la app"
            else -> null
        }
        return CatalogItem(info, playable = badge == null, badge = badge)
    }
}

class PostLoginVmFactory(
    private val repo: GameRepository,
    private val registry: GameRegistry
) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T = when {
        modelClass.isAssignableFrom(HomeViewModel::class.java) -> HomeViewModel(repo, registry)
        modelClass.isAssignableFrom(PlayViewModel::class.java) -> PlayViewModel(repo)
        else -> throw IllegalArgumentException("ViewModel desconocido: ${modelClass.name}")
    } as T
}
