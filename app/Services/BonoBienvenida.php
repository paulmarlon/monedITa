<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Otorga las monedas de cortesia al registrarse.
 * Se usa desde el registro web y desde el registro API.
 */
class BonoBienvenida
{
    public const MONTO = '3.00';
    public const TIPO  = 'BONO_BIENVENIDA';

    /**
     * @return bool true si se acredito; false si no hay ciclo activo o ya se habia otorgado.
     */
    public static function otorgar(User $user): bool
    {
        // ciclo_id es obligatorio en el libro mayor
        $ciclo = Ciclo::vigente();

        if (! $ciclo) {
            return false;
        }

        return DB::transaction(function () use ($user, $ciclo) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['saldo_actual' => 0]
            );

            // Bloqueo de fila y control de idempotencia: un solo bono por usuario
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $yaOtorgado = Transaccion::where('receptor_id', $user->id)
                ->where('tipo_operacion', self::TIPO)
                ->exists();

            if ($yaOtorgado) {
                return false;
            }

            $wallet->saldo_actual = bcadd((string) $wallet->saldo_actual, self::MONTO, 2);
            $wallet->save();

            Transaccion::create([
                'ciclo_id'       => $ciclo->id,
                'emisor_id'      => null, // el sistema
                'receptor_id'    => $user->id,
                'monto'          => self::MONTO,
                'tipo_operacion' => self::TIPO,
                'observacion'    => 'Monedas de cortesia por registro',
            ]);

            return true;
        });
    }
}
