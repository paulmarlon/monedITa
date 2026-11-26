package com.example.authapp

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.viewModels
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.systemBarsPadding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.authapp.ui.HomeScreen
import com.example.authapp.ui.LoginScreen
import com.example.authapp.ui.SessionState
import com.example.authapp.ui.SessionViewModel
import com.example.authapp.ui.SessionViewModelFactory
import com.example.authapp.ui.TwoFactorScreen

class MainActivity : ComponentActivity() {

    private val viewModel: SessionViewModel by viewModels {
        SessionViewModelFactory((application as App).repository)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            MaterialTheme {
                Surface(Modifier.fillMaxSize().systemBarsPadding()) {
                    val state by viewModel.state.collectAsStateWithLifecycle()
                    when (val s = state) {
                        is SessionState.Checking -> Column(
                            Modifier.fillMaxSize(),
                            verticalArrangement = Arrangement.Center,
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) { CircularProgressIndicator() }

                        is SessionState.Idle -> LoginScreen(
                            error = s.error,
                            busy = s.busy,
                            onLogin = viewModel::login
                        )

                        is SessionState.RequiresTwoFactor -> TwoFactorScreen(
                            state = s,
                            onVerify = viewModel::verifyCode,
                            onResend = viewModel::resendCode,
                            onBack = viewModel::backToLogin
                        )

                        is SessionState.Authenticated -> HomeScreen(
                            profile = s.profile,
                            busy = s.busy,
                            onLogout = viewModel::logout
                        )

                        is SessionState.Error -> Column(
                            Modifier.fillMaxSize().padding(24.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(s.message)
                            Button(onClick = viewModel::bootstrap) { Text("Reintentar") }
                            OutlinedButton(onClick = viewModel::clearLocalSession) {
                                Text("Cerrar sesión local")
                            }
                        }
                    }
                }
            }
        }
    }
}
