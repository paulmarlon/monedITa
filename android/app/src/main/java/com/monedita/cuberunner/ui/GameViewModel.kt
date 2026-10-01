package com.monedita.cuberunner.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.monedita.cuberunner.data.ApiResult
import com.monedita.cuberunner.data.GameRepository
import com.monedita.cuberunner.data.RegisterRequest
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class GameViewModel(private val repo: GameRepository) : ViewModel() {

    private val slug = "cube_runner"

    private val _saldo = MutableStateFlow<Double?>(null)
    val saldo: StateFlow<Double?> = _saldo

    fun haySesion() = repo.haySesion()

    fun login(registro: String, password: String, onOk: () -> Unit, onError: (String) -> Unit) =
        viewModelScope.launch {
            when (val r = repo.login(registro, password)) {
                is ApiResult.Ok -> { _saldo.value = r.data.saldo_actual; onOk() }
                is ApiResult.Fail -> onError(
                    if (r.code == 401) "Registro o contrasena incorrectos" else r.message
                )
            }
        }

    fun register(req: RegisterRequest, onOk: (bono: Double) -> Unit, onError: (String) -> Unit) =
        viewModelScope.launch {
            when (val r = repo.register(req)) {
                is ApiResult.Ok -> {
                    _saldo.value = r.data.saldo_actual
                    onOk(r.data.bono_otorgado ?: 0.0)
                }
                is ApiResult.Fail -> onError(r.message)
            }
        }

    /** Actualiza el saldo desde el servidor (al volver a la pantalla principal). */
    fun refrescarSaldo() = viewModelScope.launch {
        when (val r = repo.me()) {
            is ApiResult.Ok -> _saldo.value = r.data.saldo_actual
            is ApiResult.Fail -> if (r.code == 401) repo.cerrarSesionLocal()
        }
    }

    fun empezar(onOk: () -> Unit, onError: (String) -> Unit) = viewModelScope.launch {
        when (val r = repo.iniciar(slug)) {
            is ApiResult.Ok -> { _saldo.value = r.data.nuevo_saldo; onOk() }
            is ApiResult.Fail -> {
                if (r.code == 401) repo.cerrarSesionLocal()
                onError(
                    when (r.code) {
                        402 -> "No tienes fichas suficientes"
                        401 -> "Sesion expirada, inicia sesion de nuevo"
                        409 -> "No hay un ciclo activo"
                        else -> r.message
                    }
                )
            }
        }
    }

    fun terminar(puntaje: Int, onResult: (esRecord: Boolean) -> Unit, onError: (String) -> Unit = {}) =
        viewModelScope.launch {
            when (val r = repo.guardarPuntaje(slug, puntaje)) {
                is ApiResult.Ok -> onResult(r.data.es_record == true)
                is ApiResult.Fail -> onError(r.message)
            }
        }
}
