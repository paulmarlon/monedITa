package com.example.authapp.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import java.util.UUID

private val Context.sessionDataStore by preferencesDataStore(name = "session")

class SessionStore(private val context: Context) {

    private object Keys {
        val TOKEN = stringPreferencesKey("token")
        val DEVICE_ID = stringPreferencesKey("device_id")
        val DEVICE_LABEL = stringPreferencesKey("device_label")
    }

    suspend fun getToken(): String? =
        context.sessionDataStore.data.map { it[Keys.TOKEN] }.first()

    suspend fun getDeviceLabel(): String? =
        context.sessionDataStore.data.map { it[Keys.DEVICE_LABEL] }.first()

    /** UUID único por instalación. Se crea una sola vez y NUNCA se borra al cerrar sesión. */
    suspend fun getOrCreateDeviceId(): String {
        context.sessionDataStore.edit { prefs ->
            if (prefs[Keys.DEVICE_ID].isNullOrBlank()) {
                prefs[Keys.DEVICE_ID] = UUID.randomUUID().toString()
            }
        }
        return context.sessionDataStore.data.map { it[Keys.DEVICE_ID] }.first()!!
    }

    suspend fun saveSession(token: String, deviceLabel: String) {
        context.sessionDataStore.edit {
            it[Keys.TOKEN] = token
            it[Keys.DEVICE_LABEL] = deviceLabel
        }
    }

    /** Borra token y etiqueta de dispositivo; conserva el device_id. */
    suspend fun clearSession() {
        context.sessionDataStore.edit {
            it.remove(Keys.TOKEN)
            it.remove(Keys.DEVICE_LABEL)
        }
    }
}
