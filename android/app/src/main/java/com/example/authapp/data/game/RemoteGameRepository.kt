package com.example.authapp.data.game

import com.example.authapp.core.game.GameResult
import com.example.authapp.core.game.GameSession
import com.example.authapp.data.local.SessionStore
import com.example.authapp.data.model.GuardarPuntajeRequest
import com.example.authapp.data.model.RankingResponse
import com.example.authapp.data.remote.ApiService
import com.example.authapp.data.remote.apiErrorMessage
import com.example.authapp.data.repository.ApiResult
import kotlinx.coroutines.CancellationException
import retrofit2.Response
import java.io.IOException

/**
 * Backend real (Laravel + Sanctum): /juegos/{slug}/iniciar, /juegos/guardar-puntaje, /juegos/{slug}/ranking y /me.
 * Ciclo, puntaje individual y equipo todavía no los expone la API: quedan en null y la UI no los muestra.
 * Ante un 401 limpia el token y avisa vía [onUnauthorized] (el usuario vuelve al login).
 */
class RemoteGameRepository(
    private val api: ApiService,
    private val store: SessionStore,
    private val onUnauthorized: suspend () -> Unit
) : GameRepository {

    override suspend fun dashboard(slugs: List<String>): ApiResult<DashboardData?> = safeCall {
        val token = store.getToken() ?: return@safeCall expired()
        val bearer = "Bearer $token"

        // Créditos = saldo de la billetera (GET /me ya lo devuelve)
        var credits: Double? = null
        val me = api.me(bearer)
        when {
            me.isSuccessful -> credits = me.body()?.saldoActual
            me.code() == 401 -> return@safeCall expired()
        }

        val games = mutableListOf<GameRanking>()
        for (slug in slugs) {
            val resp = api.ranking(slug, bearer)
            when {
                resp.isSuccessful -> resp.body()?.takeIf { it.success }?.let { games += it.toDomain(slug) }
                resp.code() == 401 -> return@safeCall expired()
                resp.code() == 404 -> Unit // juego aún no registrado/activo en el servidor: se omite
                else -> return@safeCall failure(resp)
            }
        }
        ApiResult.Success(DashboardData(credits = credits, games = games))
    }

    /** No existe endpoint de catálogo: se usa el de los módulos instalados. */
    override suspend fun catalog(): ApiResult<List<GameInfo>?> = ApiResult.Success(null)

    override suspend fun startGame(slug: String): ApiResult<StartedGame> = safeCall {
        val token = store.getToken() ?: return@safeCall expired()
        val resp = api.iniciarJuego(slug, "Bearer $token")
        when {
            resp.isSuccessful -> {
                val b = resp.body()
                val id = b?.partidaId
                if (b == null || !b.success || id.isNullOrBlank()) {
                    ApiResult.Error(b?.message ?: "Respuesta inválida del servidor.")
                } else {
                    ApiResult.Success(StartedGame(GameSession(sessionId = id, slug = slug), b.fichasUsadas, b.nuevoSaldo))
                }
            }
            resp.code() == 401 -> expired()
            resp.code() == 404 -> {
                // Indica QUÉ slug pidió la app, para comparar con la tabla `juegos`
                val e = failure(resp)
                if (e.message.contains("juego", ignoreCase = true)) ApiResult.Error("${e.message} (slug: $slug)", code = 404) else e
            }
            else -> failure(resp)
        }
    }

    override suspend fun submitScore(session: GameSession, result: GameResult): ApiResult<SubmitResult> = safeCall {
        val token = store.getToken() ?: return@safeCall expired()
        val resp = api.guardarPuntaje(
            bearer = "Bearer $token",
            idempotencyKey = session.sessionId, // la partida_id identifica la partida
            body = GuardarPuntajeRequest(slug = session.slug, puntaje = result.score, partidaId = session.sessionId)
        )
        when {
            resp.isSuccessful -> {
                val b = resp.body()
                if (b == null || !b.success) {
                    ApiResult.Error(b?.message ?: "Respuesta inválida del servidor.")
                } else {
                    ApiResult.Success(
                        SubmitResult(
                            score = b.puntaje ?: result.score,
                            isPersonalBest = b.esRecord == true,
                            bestScore = b.mejorPuntaje ?: result.score,
                            rank = fetchMyRank(token, session.slug)
                        )
                    )
                }
            }
            resp.code() == 401 -> expired()
            else -> failure(resp)
        }
    }

    /** El puntaje ya está guardado; si esto falla, simplemente no se muestra la posición. */
    private suspend fun fetchMyRank(token: String, slug: String): Int? = try {
        api.ranking(slug, "Bearer $token").body()?.miPosicion
    } catch (e: CancellationException) {
        throw e
    } catch (_: Exception) {
        null
    }

    private suspend fun expired(): ApiResult<Nothing> {
        onUnauthorized()
        return ApiResult.Unauthorized
    }

    private fun failure(resp: Response<*>): ApiResult.Error {
        val code = resp.code()
        val default = when (code) {
            402 -> "Créditos insuficientes para iniciar la partida."
            403 -> "Partida inválida, expirada o ya registrada."
            404 -> "Juego no encontrado o inactivo."
            409 -> "No hay un ciclo académico activo."
            422 -> "Datos inválidos."
            429 -> "Demasiadas solicitudes. Espera un momento."
            else -> "Error del servidor ($code)."
        }
        // 422 y 429 los genera Laravel en inglés: usamos nuestro texto. El resto trae mensaje en español.
        val msg = if (code == 422 || code == 429) default else resp.apiErrorMessage(default)
        return ApiResult.Error(msg, code = code)
    }

    private fun RankingResponse.toDomain(slug: String) = GameRanking(
        slug = slug,
        title = juego ?: slug,
        top = top.orEmpty().map {
            RankEntry(it.posicion ?: 0, it.alias ?: "(sin alias)", it.avatar, it.puntaje ?: 0, it.soyYo == true)
        },
        myPosition = miPosicion,
        myBest = miMejor
    )

    private inline fun <T> safeCall(block: () -> ApiResult<T>): ApiResult<T> =
        try {
            block()
        } catch (e: CancellationException) {
            throw e
        } catch (e: IOException) {
            // Incluye timeouts: el servidor pudo haber procesado la petición.
            ApiResult.Error("No se pudo conectar con el servidor. Revisa tu conexión.", network = true)
        } catch (e: Exception) {
            ApiResult.Error("Error inesperado: ${e.localizedMessage ?: "desconocido"}")
        }
}
