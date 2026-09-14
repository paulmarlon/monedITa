<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    public function index()
    {
        $historial = DB::table('auditoria_accesos')
            ->leftJoin('users', 'auditoria_accesos.user_id', '=', 'users.id')
            ->select(
                'auditoria_accesos.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderByDesc('auditoria_accesos.created_at')
            ->get();

        return view('admin.sessions.historial', compact('historial'));
    }
}
