<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionAuditController extends Controller
{
    public function index()
    {
        // Consultamos las sesiones activas unidas con la tabla users para ver el nombre/alias
        $sesiones = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select(
                'sessions.id as session_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
                'users.alias as user_alias'
            )
            ->orderByDesc('sessions.last_activity')
            ->get();

        return view('admin.sessions.index', compact('sesiones'));
    }

    // Opcional: Permitir al admin expulsar/cerrar la sesión de alguien por la fuerza
    public function destroy(int $id)
    {
        DB::table('sessions')->where('id', $id)->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Sesión cerrada y usuario expulsado correctamente.');
    }
}
