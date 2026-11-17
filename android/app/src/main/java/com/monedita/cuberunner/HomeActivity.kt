package com.monedita.cuberunner

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory
import kotlinx.coroutines.launch

/** Menu principal: saldo, jugar, ranking y cerrar sesion. */
class HomeActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        if (!vm.haySesion()) {
            irAlLogin()
            return
        }

        setContentView(R.layout.activity_home)

        val tvAlias = findViewById<TextView>(R.id.tvAlias)
        val tvSaldo = findViewById<TextView>(R.id.tvSaldo)
        val tvLog = findViewById<TextView>(R.id.tvLog)
        val btnJugar = findViewById<Button>(R.id.btnJugar)

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch { vm.saldo.collect { tvSaldo.text = "Saldo: ${it ?: "-"} monedas" } }
                launch { vm.alias.collect { tvAlias.text = if (it != null) "Hola, $it" else "Hola" } }
            }
        }

        btnJugar.setOnClickListener {
            btnJugar.isEnabled = false
            tvLog.text = "Cobrando ficha..."
            vm.empezar(
                onOk = {
                    tvLog.text = ""
                    btnJugar.isEnabled = true
                    startActivity(Intent(this, GameActivity::class.java))
                },
                onError = { msg ->
                    btnJugar.isEnabled = true
                    if (!vm.haySesion()) irAlLogin() else tvLog.text = msg
                }
            )
        }

        findViewById<Button>(R.id.btnRanking).setOnClickListener {
            startActivity(Intent(this, RankingActivity::class.java))
        }

        findViewById<Button>(R.id.btnSalir).setOnClickListener {
            vm.logout { irAlLogin() }
        }
    }

    override fun onResume() {
        super.onResume()
        // Al volver de jugar o del ranking, trae el saldo real del servidor
        if (vm.haySesion()) vm.refrescarSaldo(onExpirada = { irAlLogin() })
    }

    private fun irAlLogin() {
        startActivity(
            Intent(this, MainActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
        )
        finish()
    }
}
