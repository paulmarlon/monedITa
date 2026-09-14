<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionAuditController extends Controller
{
    public function index()
    {
        // Apuntamos explícitamente al esquema integrador.sessions
        $sesiones = DB::table('integrador.sessions')
            ->leftJoin('users', 'integrador.sessions.user_id', '=', 'users.id')
            ->select(
                'integrador.sessions.id as session_id',
                'integrador.sessions.ip_address',
                'integrador.sessions.user_agent',
                'integrador.sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
                'users.alias as user_alias'
            )
            ->orderByDesc('integrador.sessions.last_activity')
            ->get();

        return view('admin.sessions.index', compact('sesiones'));
    }

    // Permitir al admin expulsar/cerrar la sesión de alguien por la fuerza
    public function destroy(string $id) // Nota: el ID de sessions en Laravel suele ser string (varchar), cámbialo de int a string para evitar errores de tipo.
    {
        DB::table('integrador.sessions')->where('id', $id)->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Sesión cerrada y usuario expulsado correctamente.');
    }
}
