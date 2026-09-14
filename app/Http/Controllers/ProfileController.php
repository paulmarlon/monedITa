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
            // Si ya tenía un avatar anterior y es una URL completa de Supabase, limpiamos
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                // Si guardabas ruta relativa antes
                if (Storage::exists($user->avatar)) {
                    Storage::delete($user->avatar);
                }
            } elseif ($user->avatar && str_starts_with($user->avatar, 'http')) {
                // Si ya guardaba URL completa, extraemos la ruta para borrarla en S3
                $oldPath = str_replace(Storage::url(''), '', $user->avatar);
                Storage::delete($oldPath);
            }

            // Guardamos el nuevo archivo en el disco por defecto (S3) dentro de tapita/avatars
            $path = $request->file('avatar')->store('tapita/avatars', 's3');

            // Guardamos la URL COMPLETA en la base de datos
            $user->avatar = Storage::url($path);
        }

        // Como $user viene directamente de User::find(), el método save() funcionará perfectamente
        $user->save();

        return redirect()->route('profile.edit')->with('success', '¡Perfil actualizado correctamente!');
    }
}
