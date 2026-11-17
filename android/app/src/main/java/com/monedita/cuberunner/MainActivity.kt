package com.monedita.cuberunner

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory

/** Pantalla de inicio de sesion. Si ya hay sesion guardada, pasa directo al menu. */
class MainActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        if (vm.haySesion()) {
            irAlMenu()
            return
        }

        setContentView(R.layout.activity_main)

        val etRegistro = findViewById<EditText>(R.id.etRegistro)
        val etPassword = findViewById<EditText>(R.id.etPassword)
        val tvLog = findViewById<TextView>(R.id.tvLog)
        val btnLogin = findViewById<Button>(R.id.btnLogin)

        btnLogin.setOnClickListener {
            val registro = etRegistro.text.toString().trim()
            val pass = etPassword.text.toString()
            if (registro.isEmpty() || pass.isEmpty()) {
                tvLog.text = "Escribe tu registro y contraseña"
                return@setOnClickListener
            }
            btnLogin.isEnabled = false
            tvLog.text = "Conectando..."
            vm.login(
                registro, pass,
                onOk = { irAlMenu() },
                onError = { tvLog.text = it; btnLogin.isEnabled = true }
            )
        }

        findViewById<Button>(R.id.btnRegistro).setOnClickListener {
            startActivity(Intent(this, RegisterActivity::class.java))
        }
    }

    private fun irAlMenu() {
        startActivity(
            Intent(this, HomeActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
        )
        finish()
    }
}
