package com.monedita.cuberunner

import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.monedita.cuberunner.data.RegisterRequest
import com.monedita.cuberunner.ui.GameViewModel
import com.monedita.cuberunner.ui.GameViewModelFactory

class RegisterActivity : AppCompatActivity() {

    private val vm: GameViewModel by viewModels { GameViewModelFactory(applicationContext) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_register)

        val etRegistro = findViewById<EditText>(R.id.etRegRegistro)
        val etNombre = findViewById<EditText>(R.id.etRegNombre)
        val etAlias = findViewById<EditText>(R.id.etRegAlias)
        val etEmail = findViewById<EditText>(R.id.etRegEmail)
        val etPass = findViewById<EditText>(R.id.etRegPassword)
        val etPass2 = findViewById<EditText>(R.id.etRegPassword2)
        val tvMsg = findViewById<TextView>(R.id.tvRegMsg)
        val btnCrear = findViewById<Button>(R.id.btnCrearCuenta)

        btnCrear.setOnClickListener {
            val registro = etRegistro.text.toString().trim()
            val nombre = etNombre.text.toString().trim()
            val alias = etAlias.text.toString().trim()
            val email = etEmail.text.toString().trim()
            val pass = etPass.text.toString()
            val pass2 = etPass2.text.toString()

            if (registro.isEmpty() || nombre.isEmpty() || alias.isEmpty() || email.isEmpty() || pass.isEmpty()) {
                tvMsg.text = "Completa todos los campos"
                return@setOnClickListener
            }
            if (pass != pass2) {
                tvMsg.text = "Las contraseñas no coinciden"
                return@setOnClickListener
            }

            btnCrear.isEnabled = false
            tvMsg.text = "Creando cuenta..."

            vm.register(
                RegisterRequest(registro, nombre, alias, email, pass, pass2, "android"),
                onOk = { bono ->
                    val extra = if (bono > 0) " Recibiste $bono monedas de cortesía." else ""
                    Toast.makeText(this, "Cuenta creada.$extra", Toast.LENGTH_LONG).show()
                    finish() // MainActivity actualiza el saldo al volver (onResume)
                },
                onError = { msg ->
                    tvMsg.text = msg
                    btnCrear.isEnabled = true
                }
            )
        }

        findViewById<Button>(R.id.btnVolver).setOnClickListener { finish() }
    }
}
