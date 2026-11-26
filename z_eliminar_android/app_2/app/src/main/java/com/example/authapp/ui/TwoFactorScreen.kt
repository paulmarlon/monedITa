package com.example.authapp.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.delay

@Composable
fun TwoFactorScreen(
    state: SessionState.RequiresTwoFactor,
    onVerify: (code: String) -> Unit,
    onResend: () -> Unit,
    onBack: () -> Unit
) {
    // Se limpia cuando llega un nuevo challengeId (reenvío)
    var code by rememberSaveable(state.challengeId) { mutableStateOf("") }
    var remaining by remember(state.challengeId, state.expiresIn) { mutableIntStateOf(state.expiresIn) }

    LaunchedEffect(state.challengeId, state.expiresIn) {
        remaining = state.expiresIn
        while (remaining > 0) {
            delay(1000)
            remaining -= 1
        }
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("Verificación en dos pasos", style = MaterialTheme.typography.headlineMedium)

        state.message?.let { Text(it, style = MaterialTheme.typography.bodyMedium) }
        state.emailMasked?.let {
            Text("Código enviado a $it", style = MaterialTheme.typography.bodyMedium)
        }

        OutlinedTextField(
            value = code,
            onValueChange = { code = it.filter(Char::isDigit).take(6) },
            label = { Text("Código de 6 dígitos") },
            singleLine = true,
            enabled = !state.busy,
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.NumberPassword,
                imeAction = ImeAction.Done
            ),
            keyboardActions = KeyboardActions(onDone = { if (code.length == 6) onVerify(code) }),
            modifier = Modifier.fillMaxWidth()
        )

        Text(
            text = if (remaining > 0) {
                "Expira en %02d:%02d".format(remaining / 60, remaining % 60)
            } else {
                "El código expiró. Solicita uno nuevo."
            },
            style = MaterialTheme.typography.bodySmall
        )

        state.info?.let { Text(it, color = MaterialTheme.colorScheme.primary) }
        state.error?.let { Text(it, color = MaterialTheme.colorScheme.error) }

        Button(
            onClick = { onVerify(code) },
            enabled = !state.busy && code.length == 6,
            modifier = Modifier.fillMaxWidth()
        ) {
            if (state.busy) {
                CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp)
            } else {
                Text("Verificar")
            }
        }

        OutlinedButton(
            onClick = onResend,
            enabled = !state.busy,
            modifier = Modifier.fillMaxWidth()
        ) { Text("Reenviar código") }

        TextButton(onClick = onBack, enabled = !state.busy) { Text("Volver al login") }
    }
}
