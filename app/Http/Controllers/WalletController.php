<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ciclo;
use App\Models\Wallet;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <--- Importamos la Facade Auth
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    // Mostrar la billetera del usuario actual con su historial de movimientos
    public function index()
    {
        $user = Auth::user();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['saldo_actual' => 0.00]
        );

        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        if (!$cicloActivo) {
            return view('wallets.index', [
                'wallet' => $wallet,
                'transacciones' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'usuarios' => \App\Models\User::where('id', '!=', $user->id)->get(),
                'cicloActivo' => null,
                'causaComun' => null,
                'aportesCausa' => collect()
            ]);
        }

        // ==========================================
        // 1. OBTENER DATOS DEL FONDO ECO-SALESIANO
        // ==========================================
        $causaComun = \App\Models\CausaComun::firstOrCreate(
            ['ciclo_id' => $cicloActivo->id],
            ['total_acumulado' => 0.00, 'descripcion' => 'Fondo ecológico y de reserva comunitaria']
        );

        // CORREGIDO: Buscando por el nuevo tipo de operación
        $aportesCausa = \App\Models\Transaccion::where('ciclo_id', $cicloActivo->id)
            ->where('tipo_operacion', 'FONDO_ECO_SALESIANO')
            ->latest('created_at')
            ->take(5)
            ->get();

        // ==========================================
        // 2. TRANSACCIONES Y SALDO PROGRESIVO
        // ==========================================
        $todasCiclo = \App\Models\Transaccion::where('ciclo_id', $cicloActivo->id)
            ->where(function ($query) use ($user) {
                $query->where('emisor_id', $user->id)
                    ->orWhere('receptor_id', $user->id);
            })
            ->oldest('created_at')
            ->get();

        $acumuladorHistorico = 0.00;
        foreach ($todasCiclo as $tx) {
            // CORREGIDO: Evita que al supervisor se le reste saldo por acreditar tapitas
            if ($tx->tipo_operacion === 'FONDO_ECO_SALESIANO') {
                if ($tx->receptor_id == $user->id) {
                    $acumuladorHistorico += $tx->monto; // Solo suma si es el estudiante receptor
                }
                // Si el usuario actual es el supervisor (emisor), no hace nada (no resta)
            } else {
                // Comportamiento normal para transferencias entre usuarios
                if ($tx->receptor_id == $user->id) {
                    $acumuladorHistorico += $tx->monto;
                }
                if ($tx->emisor_id == $user->id) {
                    $acumuladorHistorico -= $tx->monto;
                }
            }

            $tx->saldo_despues = $acumuladorHistorico;
        }

        // Sincronizamos el saldo actual de la tarjeta con el cálculo real del ciclo
        $wallet->saldo_actual = $acumuladorHistorico;

        // Ordenar de más reciente a más antiguo para la vista
        $transaccionesOrdenadas = $todasCiclo->sortByDesc('created_at');

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $currentItems = $transaccionesOrdenadas->slice(($page - 1) * $perPage, $perPage)->all();

        $transacciones = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $transaccionesOrdenadas->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $usuarios = \App\Models\User::where('id', '!=', $user->id)->get();

        return view('wallets.index', compact('wallet', 'transacciones', 'usuarios', 'cicloActivo', 'causaComun', 'aportesCausa'));
    }

    // Procesar la transferencia de moneditas a otro usuario
    public function transferir(Request $request)
    {
        $request->validate([
            'receptor_id' => ['required', 'exists:users,id', 'different:' . Auth::id()],
            'monto'       => ['required', 'numeric', 'min:0.01'],
            'observacion' => ['nullable', 'string', 'max:255']
        ]);

        $emisor = Auth::user();
        $receptor = User::findOrFail($request->receptor_id);
        $monto = $request->monto;

        if ($emisor->wallet->saldo_actual < $monto) {
            return back()->withErrors(['monto' => 'No tienes suficientes moneditas para realizar esta transferencia.']);
        }

        DB::transaction(function () use ($emisor, $receptor, $monto, $request) {
            // Descontar al emisor
            $emisor->wallet->decrement('saldo_actual', $monto);

            // Acreditar al receptor
            $receptor->wallet->increment('saldo_actual', $monto);

            // Registrar en el Libro Mayor
            Transaccion::create([
                'ciclo_id'       => 1, // Cambiar luego por el ciclo activo
                'emisor_id'      => $emisor->id,
                'receptor_id'    => $receptor->id,
                'monto'          => $monto,
                'tipo_operacion' => 'TRANSFERENCIA_USUARIO',
                'observacion'    => $request->observacion ?? 'Transferencia entre compañeros'
            ]);
        });

        return redirect()->route('wallets.index')->with('success', '¡Moneditas enviadas con éxito!');
    }
    public function acreditar(Request $request)
    {
        abort_unless(Gate::allows('acreditar_wallet'), 403, 'No tienes autorización para realizar acreditaciones.');

        $request->validate([
            'receptor_id'      => ['required', 'exists:users,id'],
            'tapitas_cantidad' => ['required', 'integer', 'min:1']
        ]);

        $receptor = User::findOrFail($request->receptor_id);
        $tapitas = $request->tapitas_cantidad;
        $monto = $tapitas * 1.00; // Regla: 1 tapita = 1 monedita

        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();
        $cicloId = $cicilActivoId = $cicloActivo ? $cicloActivo->id : 1;

        DB::transaction(function () use ($receptor, $monto, $tapitas, $cicloId) {
            // 1. Asegurar wallet del estudiante y aumentar saldo
            $wallet = $receptor->wallet()->firstOrCreate(['user_id' => $receptor->id], ['saldo_actual' => 0.00]);
            $wallet->increment('saldo_actual', $monto);

            // 2. Actualizar el total acumulado en el Fondo Eco-Salesiano
            $causaComun = \App\Models\CausaComun::firstOrCreate(
                ['ciclo_id' => $cicloId],
                ['total_acumulado' => 0.00, 'descripcion' => 'Fondo ecológico y de reserva comunitaria']
            );
            $causaComun->increment('total_acumulado', $monto);

            // 3. Registrar transacción con el tipo correcto
            Transaccion::create([
                'ciclo_id'       => $cicloId,
                'emisor_id'      => Auth::id(),
                'receptor_id'    => $receptor->id,
                'monto'          => $monto,
                'tipo_operacion' => 'FONDO_ECO_SALESIANO',
                'observacion'    => "Aporte ecológico: Reciclaje de " . $tapitas . " tapitas para el Fondo Salesiano."
            ]);
        });

        return back()->with('success', '¡Canje de ' . $tapitas . ' tapitas registrado con éxito!');
    }
}
