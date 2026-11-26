package com.monedita.cuberunner

import android.graphics.Typeface
import android.os.Bundle
import android.widget.Button
import android.widget.LinearLayout
import android.widget.TextView
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory

class RankingActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_ranking)

        val tvTitulo = findViewById<TextView>(R.id.tvRankTitulo)
        val tvMiPos = findViewById<TextView>(R.id.tvMiPosicion)
        val lista = findViewById<LinearLayout>(R.id.llRanking)
        val tvMsg = findViewById<TextView>(R.id.tvRankMsg)

        findViewById<Button>(R.id.btnRankVolver).setOnClickListener { finish() }

        tvMsg.text = "Cargando..."

        vm.ranking(
            onOk = { r ->
                tvMsg.text = ""
                tvTitulo.text = "Mejores puntajes - ${r.juego ?: ""}"
                tvMiPos.text = if (r.mi_posicion != null)
                    "Tu posición: #${r.mi_posicion}  (tu mejor: ${r.mi_mejor})"
                else
                    "Aún no tienes puntajes"

                lista.removeAllViews()
                val top = r.top.orEmpty()
                if (top.isEmpty()) tvMsg.text = "Todavía no hay puntajes"

                top.forEach { item ->
                    val medalla = when (item.posicion) {
                        1 -> "🥇"
                        2 -> "🥈"
                        3 -> "🥉"
                        else -> "#${item.posicion}"
                    }
                    val yo = item.soy_yo == true
                    lista.addView(TextView(this).apply {
                        text = "$medalla  ${item.alias}${if (yo) " (tú)" else ""}   —   ${item.puntaje}"
                        textSize = 18f
                        setPadding(0, 16, 0, 16)
                        if (yo) setTypeface(typeface, Typeface.BOLD)
                    })
                }
            },
            onError = { tvMsg.text = it }
        )
    }
}
