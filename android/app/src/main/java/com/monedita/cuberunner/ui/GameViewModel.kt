package com.monedita.cuberunner.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.monedita.cuberunner.data.ApiResult
import com.monedita.cuberunner.data.GameRepository
import com.monedita.cuberunner.data.RankingResponse
import com.monedita.cuberunner.data.RegisterRequest
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class GameViewModel(private val repo: GameRepository) : ViewModel() {

    private val slug = "cube_runner"

    private val _saldo = MutableStateFlow<Double?>(null)
    val saldo: StateFlow<Double?> = _saldo

    private val _alias = MutableStateFlow<String?>(null)
    val alias: StateFlow<String?> = _alias

    fun haySesion() = repo.haySesion()

    fun login(registro: String, password: String, onOk: () -> Unit, onError: (String) -> Unit) =
        viewModelScope.launch {
            when (val r = repo.login(registro, password)) {
                is ApiResult.Ok -> {
                    _saldo.value = r.data.saldo_actual
                    _alias.value = r.data.user?.alias
                    onOk()
                }
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
                    _alias.value = r.data.user?.alias
                    onOk(r.data.bono_otorgado ?: 0.0)
                }
                is ApiResult.Fail -> onError(r.message)
            }
        }

    /** Trae alias y saldo reales. Si la sesion vencio, avisa para volver al login. */
    fun refrescarSaldo(onExpirada: () -> Unit = {}) = viewModelScope.launch {
        when (val r = repo.me()) {
            is ApiResult.Ok -> {
                _saldo.value = r.data.saldo_actual
                _alias.value = r.data.user?.alias
            }
            is ApiResult.Fail -> if (r.code == 401) {
                repo.cerrarSesionLocal()
                onExpirada()
            }
        }
    }

    fun logout(onDone: () -> Unit) = viewModelScope.launch {
        repo.logout()
        onDone()
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

    fun ranking(onOk: (RankingResponse) -> Unit, onError: (String) -> Unit) =
        viewModelScope.launch {
            when (val r = repo.ranking(slug)) {
                is ApiResult.Ok -> onOk(r.data)
                is ApiResult.Fail -> onError(r.message)
            }
        }
}
