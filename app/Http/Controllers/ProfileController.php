<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Team;

class ProfileController extends Controller
{
    // Mostrar la vista de edición de perfil
    public function edit()
    {
        $user = Auth::user();
        $teams = Team::all(); // Listado de equipos disponibles para unirse o cambiar

        return view('profile.edit', compact('user', 'teams'));
    }

    // Actualizar los datos del perfil
    public function update(Request $request)
    {
        // Obtener la instancia del modelo Eloquent asegurando el ID del usuario autenticado
        $user = \App\Models\User::find(Auth::id());

        // Si por alguna razón no se encuentra, redirigir o abortar
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor inicia sesión nuevamente.');
        }

        // Validaciones
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'alias' => 'required|string|max:255|unique:users,alias,' . $user->id,
            'team_id' => 'nullable|exists:teams,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Asignación de datos
        $user->email = $request->email;
        $user->alias = $request->alias;
        $user->team_id = $request->team_id;

        // Procesar la subida del avatar si existe
        if ($request->hasFile('avatar')) {
            // Si ya tenía un avatar anterior en S3 y no es una URL externa, lo borramos
            if ($user->avatar && !str_starts_with($user->avatar, 'http') && \Illuminate\Support\Facades\Storage::disk('s3')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('s3')->delete($user->avatar);
            }

            // Guardamos en el disco s3 dentro de la carpeta tapita/avatars
            $path = $request->file('avatar')->store('tapita/avatars', 's3');

            // Opcional: Si necesitas que $user->avatar guarde la URL pública completa para mostrarla directo en la vista:
            // $user->avatar = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);

            // O si prefieres guardar solo la ruta relativa (ej: 'tapita/avatars/foto.png'):
            $user->avatar = $path;
        }

        // Como $user viene directamente de User::find(), el método save() funcionará perfectamente
        $user->save();

        return redirect()->route('profile.edit')->with('success', '¡Perfil actualizado correctamente!');
    }
}
