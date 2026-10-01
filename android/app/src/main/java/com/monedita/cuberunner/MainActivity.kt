package com.monedita.cuberunner

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val etRegistro = findViewById<EditText>(R.id.etRegistro)
        val etPassword = findViewById<EditText>(R.id.etPassword)
        val tvSaldo = findViewById<TextView>(R.id.tvSaldo)
        val tvLog = findViewById<TextView>(R.id.tvLog)

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                vm.saldo.collect { tvSaldo.text = "Saldo: ${it ?: "-"}" }
            }
        }

        findViewById<Button>(R.id.btnLogin).setOnClickListener {
            tvLog.text = "Conectando..."
            vm.login(
                etRegistro.text.toString().trim(),
                etPassword.text.toString(),
                onOk = { tvLog.text = "Login correcto" },
                onError = { tvLog.text = "Error: $it" }
            )
        }

        findViewById<Button>(R.id.btnRegistro).setOnClickListener {
            startActivity(Intent(this, RegisterActivity::class.java))
        }

        findViewById<Button>(R.id.btnJugar).setOnClickListener {
            tvLog.text = "Cobrando ficha..."
            vm.empezar(
                onOk = {
                    tvLog.text = ""
                    startActivity(Intent(this, GameActivity::class.java))
                },
                onError = { tvLog.text = "Error: $it" }
            )
        }
    }

    override fun onResume() {
        super.onResume()
        // Al volver de jugar o de registrarse, trae el saldo real del servidor
        if (vm.haySesion()) vm.refrescarSaldo()
    }
}
