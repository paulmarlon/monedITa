package com.monedita.cuberunner

import android.graphics.Color
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.view.ViewGroup.LayoutParams.MATCH_PARENT
import android.widget.Button
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.TextView
import androidx.activity.addCallback
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.monedita.cuberunner.game.GameView
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory

class GameActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val gameView = GameView(this)
        val tvResultado = TextView(this).apply {
            setTextColor(Color.WHITE); textSize = 22f; gravity = Gravity.CENTER
        }
        val btnReintentar = Button(this).apply { text = "Reintentar guardado"; visibility = View.GONE }
        val btnOtra = Button(this).apply { text = "Jugar otra vez (1 ficha)" }
        val btnSalir = Button(this).apply { text = "Salir" }

        val overlay = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            setBackgroundColor(Color.argb(190, 0, 0, 0))
            setPadding(48, 48, 48, 48)
            visibility = View.GONE
            addView(tvResultado)
            addView(btnReintentar)
            addView(btnOtra)
            addView(btnSalir)
        }

        setContentView(FrameLayout(this).apply {
            addView(gameView, FrameLayout.LayoutParams(MATCH_PARENT, MATCH_PARENT))
            addView(overlay, FrameLayout.LayoutParams(MATCH_PARENT, MATCH_PARENT))
        })

        fun guardar(score: Int) {
            tvResultado.text = "Puntaje: $score\nGuardando..."
            btnReintentar.visibility = View.GONE
            btnOtra.isEnabled = false
            vm.terminar(
                score,
                onResult = { esRecord ->
                    tvResultado.text = "Puntaje: $score\n" +
                        if (esRecord) "¡Nuevo récord!" else "Puntaje guardado"
                    btnOtra.isEnabled = true
                },
                onError = { msg ->
                    tvResultado.text = "Puntaje: $score\nNo se pudo guardar: $msg"
                    btnReintentar.visibility = View.VISIBLE
                    btnReintentar.setOnClickListener { guardar(score) }
                }
            )
        }

        gameView.onGameOver = GameView.OnGameOver { score ->
            overlay.visibility = View.VISIBLE
            guardar(score)
        }

        btnOtra.setOnClickListener {
            btnOtra.isEnabled = false
            tvResultado.text = "Cobrando ficha..."
            vm.empezar(
                onOk = { overlay.visibility = View.GONE; gameView.start() },
                onError = { msg -> tvResultado.text = msg; btnOtra.isEnabled = true }
            )
        }

        btnSalir.setOnClickListener { finish() }

        // Atras durante la partida: termina y guarda el puntaje actual
        onBackPressedDispatcher.addCallback(this) {
            if (gameView.isRunning) gameView.forceGameOver() else finish()
        }

        // La ficha ya se cobro en MainActivity, asi que arranca directo
        gameView.post { gameView.start() }
    }
}
