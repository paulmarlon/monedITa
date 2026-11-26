package com.monedita.cuberunner.game

import android.content.Context
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.RectF
import android.util.AttributeSet
import android.view.MotionEvent
import android.view.View
import java.util.Random
import kotlin.math.min

/**
 * Cube Runner vertical. Toca para saltar; manten presionado para saltar mas alto.
 * Cada 400 puntos sube el nivel y aparece un obstaculo nuevo.
 */
class GameView @JvmOverloads constructor(
    context: Context, attrs: AttributeSet? = null
) : View(context, attrs) {

    fun interface OnGameOver { fun onGameOver(score: Int) }

    var onGameOver: OnGameOver? = null
    var isRunning = false
        private set

    private class Obs(val rect: RectF, val factor: Float, val paint: Paint)
    private class Star(var x: Float, val y: Float, val size: Float, val depth: Float)

    private fun paint(hex: String) = Paint(Paint.ANTI_ALIAS_FLAG).apply { color = Color.parseColor(hex) }

    private val bg = paint("#1B1F3B")
    private val ground = paint("#3A3F6B")
    private val player = paint("#4CAF50")
    private val starPaint = paint("#8088C8")
    private val pSmall = paint("#F44336")
    private val pWide = paint("#FF9800")
    private val pTall = paint("#9C27B0")
    private val pFly = paint("#00BCD4")
    private val pFast = paint("#FFEB3B")
    private val text = paint("#FFFFFF")
    private val textRight = paint("#FFFFFF").apply { textAlign = Paint.Align.RIGHT }
    private val banner = paint("#FFEB3B").apply { textAlign = Paint.Align.CENTER }

    private var unit = 0f
    private var groundY = 0f
    private var playerX = 0f
    private var lift = 0f
    private var vy = 0f
    private var rot = 0f
    private var speed = 0f
    private var distance = 0f
    private var sinceSpawn = 0f
    private var nextGap = 0f
    private var score = 0
    private var level = 0
    private var bannerTime = 0f
    private var last = 0L
    private val obstacles = mutableListOf<Obs>()
    private val stars = mutableListOf<Star>()
    private val rnd = Random()

    override fun onSizeChanged(w: Int, h: Int, oldw: Int, oldh: Int) {
        unit = w * 0.09f
        groundY = h * 0.75f
        playerX = w * 0.18f
        text.textSize = unit * 0.7f
        textRight.textSize = unit * 0.7f
        banner.textSize = unit * 1.4f
        stars.clear()
        repeat(25) {
            stars.add(
                Star(
                    rnd.nextFloat() * w, rnd.nextFloat() * groundY * 0.9f,
                    unit * (0.05f + rnd.nextFloat() * 0.07f), 0.1f + rnd.nextFloat() * 0.25f
                )
            )
        }
    }

    fun start() {
        if (width == 0) { post { start() }; return }
        obstacles.clear()
        lift = 0f; vy = 0f; rot = 0f; distance = 0f; score = 0
        level = 0; bannerTime = 0f; sinceSpawn = 0f
        speed = unit * 4f
        nextGap = speed * 1.6f
        last = System.nanoTime()
        isRunning = true
        invalidate()
    }

    fun forceGameOver() { if (isRunning) gameOver() }

    private fun gameOver() {
        isRunning = false
        onGameOver?.onGameOver(score)
    }

    private fun spawn() {
        val u = unit
        val gy = groundY
        val x = width.toFloat()

        // Cada nivel desbloquea un obstaculo nuevo
        val pool = mutableListOf(0)
        for (n in 1..5) if (level >= n) pool.add(n)

        var extra = 0f
        when (pool[rnd.nextInt(pool.size)]) {
            0 -> obstacles.add(Obs(RectF(x, gy - u, x + u, gy), 1f, pSmall))
            1 -> { obstacles.add(Obs(RectF(x, gy - u, x + 2f * u, gy), 1f, pWide)); extra = u }
            2 -> obstacles.add(Obs(RectF(x, gy - 2f * u, x + u, gy), 1f, pTall))
            3 -> obstacles.add(Obs(RectF(x, gy - 2.3f * u, x + 1.2f * u, gy - 1.3f * u), 1f, pFly))
            4 -> {
                obstacles.add(Obs(RectF(x, gy - u, x + u, gy), 1f, pSmall))
                obstacles.add(Obs(RectF(x + 2.4f * u, gy - u, x + 3.4f * u, gy), 1f, pSmall))
                extra = 3.4f * u
            }
            5 -> obstacles.add(Obs(RectF(x, gy - u, x + u, gy), 1.3f, pFast))
        }

        val gapSeconds = (0.85f + rnd.nextFloat() * 0.75f) * (1f - level * 0.05f)
        nextGap = speed * gapSeconds + extra
    }

    private fun update(dt: Float) {
        speed = min(unit * 9f, speed + unit * 0.2f * dt)

        lift += vy * dt
        vy -= unit * 30f * dt
        if (lift <= 0f) { lift = 0f; vy = 0f; rot = 0f } else rot += 380f * dt

        distance += speed * dt
        score = (distance / unit * 10f).toInt()

        val newLevel = min(score / 400, 5)
        if (newLevel > level) { level = newLevel; bannerTime = 1.5f }
        if (bannerTime > 0f) bannerTime -= dt

        sinceSpawn += speed * dt
        if (sinceSpawn >= nextGap) { sinceSpawn = 0f; spawn() }

        val it = obstacles.iterator()
        while (it.hasNext()) {
            val o = it.next()
            o.rect.offset(-speed * o.factor * dt, 0f)
            if (o.rect.right < 0f) it.remove()
        }

        stars.forEach { s ->
            s.x -= speed * s.depth * dt
            if (s.x < 0f) s.x += width
        }

        val m = unit * 0.12f
        val p = RectF(playerX + m, groundY - lift - unit + m, playerX + unit - m, groundY - lift - m)
        if (obstacles.any { RectF.intersects(it.rect, p) }) gameOver()
    }

    override fun onDraw(canvas: Canvas) {
        if (isRunning) {
            val now = System.nanoTime()
            val dt = min((now - last) / 1_000_000_000f, 0.05f)
            last = now
            update(dt)
        }

        canvas.drawRect(0f, 0f, width.toFloat(), height.toFloat(), bg)
        stars.forEach { canvas.drawRect(it.x, it.y, it.x + it.size, it.y + it.size, starPaint) }
        canvas.drawRect(0f, groundY, width.toFloat(), height.toFloat(), ground)

        val cx = playerX + unit / 2f
        val cy = groundY - lift - unit / 2f
        canvas.save()
        canvas.rotate(rot, cx, cy)
        canvas.drawRect(playerX, groundY - lift - unit, playerX + unit, groundY - lift, player)
        canvas.restore()

        obstacles.forEach { canvas.drawRect(it.rect, it.paint) }

        canvas.drawText("Puntaje: $score", unit * 0.5f, unit * 1.3f, text)
        canvas.drawText("Nivel ${level + 1}", width - unit * 0.5f, unit * 1.3f, textRight)
        if (bannerTime > 0f) {
            canvas.drawText("¡Nivel ${level + 1}!", width / 2f, height * 0.35f, banner)
        }

        if (isRunning) postInvalidateOnAnimation()
    }

    override fun onTouchEvent(e: MotionEvent): Boolean {
        when (e.action) {
            MotionEvent.ACTION_DOWN -> {
                if (isRunning && lift == 0f) vy = unit * 14f
            }
            MotionEvent.ACTION_UP, MotionEvent.ACTION_CANCEL -> {
                // Salto variable: soltar pronto da un salto corto
                if (vy > unit * 5f) vy = unit * 5f
            }
        }
        return true
    }
}
