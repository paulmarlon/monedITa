package com.monedita.cuberunner.data

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

class TokenStore(context: Context) {

    private val prefs = EncryptedSharedPreferences.create(
        context,
        "auth_prefs",
        MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )

    var token: String?
        get() = prefs.getString("token", null)
        set(v) = prefs.edit().putString("token", v).apply()

    /** Partida pagada pendiente de reportar (vigencia 2 h en el servidor). */
    var partidaId: String?
        get() = prefs.getString("partida_id", null)
        set(v) = prefs.edit().putString("partida_id", v).apply()

    fun clear() = prefs.edit().clear().apply()
}
