package com.example.authapp.ui.profile

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.authapp.data.model.Profile
import java.util.Locale

@Composable
fun ProfileScreen(profile: Profile, busy: Boolean, onLogout: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        Text("Perfil", style = MaterialTheme.typography.headlineMedium)

        Card(Modifier.fillMaxWidth()) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                InfoRow("Nombre", profile.name)
                InfoRow("Alias", profile.alias ?: "—")
                InfoRow("ID", profile.id.toString())
                InfoRow("Equipo", profile.teamId?.toString() ?: "Sin equipo")
                InfoRow("Saldo actual", String.format(Locale.US, "%.2f", profile.saldoActual))
                InfoRow("Dispositivo activo", profile.deviceLabel)
            }
        }

        Spacer(Modifier.weight(1f))

        Button(onClick = onLogout, enabled = !busy, modifier = Modifier.fillMaxWidth()) {
            if (busy) CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp)
            else Text("Cerrar sesión")
        }
    }
}

@Composable
private fun InfoRow(label: String, value: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, style = MaterialTheme.typography.bodyMedium)
        Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold)
    }
}
