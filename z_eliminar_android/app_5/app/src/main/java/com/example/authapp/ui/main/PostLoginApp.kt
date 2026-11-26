package com.example.authapp.ui.main

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.example.authapp.core.game.GameRegistry
import com.example.authapp.data.game.GameRepository
import com.example.authapp.data.game.ScoreEntry
import com.example.authapp.data.model.Profile
import com.example.authapp.ui.dashboard.DashboardScreen
import com.example.authapp.ui.games.GameDetailScreen
import com.example.authapp.ui.games.GamePlayScreen
import com.example.authapp.ui.games.GameResultScreen
import com.example.authapp.ui.games.GamesScreen
import com.example.authapp.ui.profile.ProfileScreen

private const val TABS = "tabs"
private const val DETAIL = "detail/{slug}"
private const val PLAY = "play/{slug}"
private const val RESULT = "result"

private val slugArg = listOf(navArgument("slug") { type = NavType.StringType })

@Composable
fun PostLoginApp(
    loginKey: String,
    profile: Profile,
    logoutBusy: Boolean,
    onLogout: () -> Unit,
    repository: GameRepository,
    registry: GameRegistry
) {
    // La clave por login evita que otro usuario herede el estado del anterior.
    val factory = remember(repository, registry) { PostLoginVmFactory(repository, registry) }
    val homeVm: HomeViewModel = viewModel(key = "home-$loginKey", factory = factory)
    val playVm: PlayViewModel = viewModel(key = "play-$loginKey", factory = factory)
    val nav = rememberNavController()

    val dashboard by homeVm.dashboard.collectAsStateWithLifecycle()
    val catalog by homeVm.catalog.collectAsStateWithLifecycle()
    val recent by playVm.recent.collectAsStateWithLifecycle()
    val creditsOverride by playVm.credits.collectAsStateWithLifecycle()

    // Créditos: el valor tras la última partida, o el del tablero (si el backend lo envía)
    val credits = creditsOverride ?: (dashboard as? DashboardUiState.Content)?.data?.credits
    val titleOf: (String) -> String = { slug ->
        catalog.firstOrNull { it.info.slug == slug }?.info?.title ?: registry.find(slug)?.title ?: slug
    }

    NavHost(navController = nav, startDestination = TABS) {

        composable(TABS) {
            TabsScreen(
                profile = profile.copy(saldoActual = creditsOverride ?: profile.saldoActual),
                logoutBusy = logoutBusy,
                onLogout = onLogout,
                dashboard = dashboard,
                catalog = catalog,
                recent = recent,
                creditsOverride = creditsOverride,
                titleOf = titleOf,
                onRefresh = homeVm::refresh,
                onOpenGame = { slug -> nav.navigate("detail/$slug") }
            )
        }

        composable(DETAIL, arguments = slugArg) { entry ->
            val slug = entry.arguments?.getString("slug").orEmpty()
            GameDetailScreen(
                item = catalog.firstOrNull { it.info.slug == slug },
                ranking = (dashboard as? DashboardUiState.Content)?.data?.games?.firstOrNull { it.slug == slug },
                credits = credits,
                onPlay = {
                    playVm.start(slug)
                    nav.navigate("play/$slug")
                },
                onBack = { nav.popBackStack() }
            )
        }

        composable(PLAY, arguments = slugArg) {
            GamePlayScreen(
                playVm = playVm,
                registry = registry,
                onGameDone = { nav.navigate(RESULT) { popUpTo(PLAY) { inclusive = true } } },
                onExit = {
                    homeVm.refresh()
                    nav.popBackStack()
                }
            )
        }

        composable(RESULT) {
            val state by playVm.state.collectAsStateWithLifecycle()
            GameResultScreen(
                state = state,
                onRetrySubmit = playVm::retrySubmit,
                onPlayAgain = { slug ->
                    playVm.start(slug) // pide partida nueva (consume crédito si corresponde)
                    nav.navigate("play/$slug") { popUpTo(RESULT) { inclusive = true } }
                },
                onBack = {
                    homeVm.refresh() // el tablero se actualiza al regresar
                    nav.popBackStack(TABS, inclusive = false)
                }
            )
        }
    }
}

private data class TabItem(val label: String, val icon: ImageVector)

private val tabItems = listOf(
    TabItem("Inicio", Icons.Default.Home),
    TabItem("Juegos", Icons.Default.PlayArrow),
    TabItem("Perfil", Icons.Default.Person)
)

@Composable
private fun TabsScreen(
    profile: Profile,
    logoutBusy: Boolean,
    onLogout: () -> Unit,
    dashboard: DashboardUiState,
    catalog: List<CatalogItem>,
    recent: List<ScoreEntry>,
    creditsOverride: Double?,
    titleOf: (String) -> String,
    onRefresh: () -> Unit,
    onOpenGame: (String) -> Unit
) {
    var tab by rememberSaveable { mutableIntStateOf(0) }

    Scaffold(
        bottomBar = {
            NavigationBar {
                tabItems.forEachIndexed { index, item ->
                    NavigationBarItem(
                        selected = tab == index,
                        onClick = { tab = index },
                        icon = { Icon(item.icon, contentDescription = item.label) },
                        label = { Text(item.label) }
                    )
                }
            }
        }
    ) { padding ->
        Box(Modifier.padding(padding)) {
            when (tab) {
                0 -> DashboardScreen(
                    state = dashboard,
                    creditsOverride = creditsOverride,
                    recent = recent,
                    titleOf = titleOf,
                    onRefresh = onRefresh,
                    onGoToGames = { tab = 1 }
                )
                1 -> GamesScreen(items = catalog, onOpen = onOpenGame)
                else -> ProfileScreen(profile, logoutBusy, onLogout)
            }
        }
    }
}
