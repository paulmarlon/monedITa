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
import com.example.authapp.data.model.Profile
import com.example.authapp.ui.dashboard.DashboardScreen
import com.example.authapp.ui.games.GamePlayScreen
import com.example.authapp.ui.games.GameResultScreen
import com.example.authapp.ui.games.GamesScreen
import com.example.authapp.ui.profile.ProfileScreen

private const val TABS = "tabs"
private const val PLAY = "play/{slug}"
private const val RESULT = "result"

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
    val factory = remember(repository) { PostLoginVmFactory(repository) }
    val homeVm: HomeViewModel = viewModel(key = "home-$loginKey", factory = factory)
    val playVm: PlayViewModel = viewModel(key = "play-$loginKey", factory = factory)
    val nav = rememberNavController()

    NavHost(navController = nav, startDestination = TABS) {

        composable(TABS) {
            TabsScreen(
                profile = profile,
                logoutBusy = logoutBusy,
                onLogout = onLogout,
                homeVm = homeVm,
                registry = registry,
                onPlay = { slug ->
                    playVm.start(slug)
                    nav.navigate("play/$slug")
                }
            )
        }

        composable(PLAY, arguments = listOf(navArgument("slug") { type = NavType.StringType })) {
            GamePlayScreen(
                playVm = playVm,
                registry = registry,
                onFinished = {
                    nav.navigate(RESULT) { popUpTo(PLAY) { inclusive = true } }
                },
                onExit = {
                    homeVm.refresh()
                    nav.popBackStack()
                }
            )
        }

        composable(RESULT) {
            // Se lee una sola vez al entrar: "Jugar de nuevo" cambia el estado del VM y no queremos parpadeos.
            val finished = remember { playVm.state.value as? PlayState.Finished }
            GameResultScreen(
                finished = finished,
                onPlayAgain = { slug ->
                    playVm.start(slug)
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
    homeVm: HomeViewModel,
    registry: GameRegistry,
    onPlay: (String) -> Unit
) {
    var tab by rememberSaveable { mutableIntStateOf(0) }
    val dashboard by homeVm.dashboard.collectAsStateWithLifecycle()
    val catalog by homeVm.catalog.collectAsStateWithLifecycle()

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
                    onRefresh = homeVm::refresh,
                    onGoToGames = { tab = 1 }
                )
                1 -> GamesScreen(
                    state = catalog,
                    registry = registry,
                    onRetry = homeVm::refresh,
                    onPlay = onPlay
                )
                else -> ProfileScreen(profile, logoutBusy, onLogout)
            }
        }
    }
}
