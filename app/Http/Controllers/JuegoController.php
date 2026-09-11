<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use Illuminate\Http\Request;

class JuegoController extends Controller
{
    /**
     * Muestra el listado de juegos.
     */
    public function index()
    {
        $juegos = Juego::latest()->paginate(10);
        return view('juegos.index', compact('juegos'));
    }

    /**
     * Muestra el formulario para crear un nuevo juego.
     */
    public function create()
    {
        return view('juegos.create');
    }

    /**
     * Almacena un nuevo juego en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:juegos,slug',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'costo_ficha' => 'required|numeric|min:0',
            'activo' => 'boolean',
        ]);

        Juego::create([
            'slug' => $request->slug,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'costo_ficha' => $request->costo_ficha,
            'activo' => $request->has('activo'),
        ]);

        return redirect()->route('juegos.index')
            ->with('success', '¡Juego registrado con éxito en el catálogo!');
    }

    /**
     * Muestra los detalles de un juego específico.
     */
    public function show(Juego $juego)
    {
        return view('juegos.show', compact('juego'));
    }

    /**
     * Muestra el formulario de edición de un juego.
     */
    public function edit(Juego $juego)
    {
        return view('juegos.edit', compact('juego'));
    }

    /**
     * Actualiza los datos de un juego existente.
     */
    public function update(Request $request, Juego $juego)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:juegos,slug,' . $juego->id,
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'costo_ficha' => 'required|numeric|min:0',
            'activo' => 'boolean',
        ]);

        $juego->update([
            'slug' => $request->slug,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'costo_ficha' => $request->costo_ficha,
            'activo' => $request->has('activo'),
        ]);

        return redirect()->route('juegos.index')
            ->with('success', '¡Juego actualizado correctamente!');
    }

    /**
     * Elimina un juego del catálogo.
     */
    public function destroy(Juego $juego)
    {
        $juego->delete(); // Aplica SoftDelete

        return redirect()->route('juegos.index')
            ->with('success', 'Minijuego enviado a la papelera correctamente.');
    }
    public function trash()
    {
        $juegos = Juego::onlyTrashed()->get();
        return view('juegos.trash', compact('juegos'));
    }

    /**
     * Restaura un juego eliminado.
     */
    public function restore(int $id)
    {
        $juego = Juego::onlyTrashed()->findOrFail($id);
        $juego->restore();

        return redirect()->route('juegos.trash')
            ->with('success', 'Minijuego restaurado correctamente.');
    }

    /**
     * Borrado definitivo (Opcional).
     */
    public function forceDelete(int $id)
    {
        $juego = Juego::onlyTrashed()->findOrFail($id);
        $juego->forceDelete();

        return redirect()->route('juegos.trash')
            ->with('success', 'Minijuego eliminado permanentemente de la base de datos.');
    }
}
