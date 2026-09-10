<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\CausaComun;
use Illuminate\Http\Request;

class CicloController extends Controller
{
    /**
     * Listado exclusivo de Ciclos Activos.
     */
    public function index()
    {
        $ciclos = Ciclo::latest()->get();
        return view('ciclos.index', compact('ciclos'));
    }

    /**
     * Listado exclusivo de Ciclos en la Papelera (SoftDeletes).
     */
    public function trash()
    {
        $ciclos = Ciclo::onlyTrashed()->latest()->get();
        return view('ciclos.trash', compact('ciclos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ciclos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $ciclo = Ciclo::create($request->all());

        // ==========================================
        // AUTOMATIZACIÓN: Crear Fondo Común (Causa Dinosaurio)
        // ==========================================
        CausaComun::firstOrCreate(
            ['ciclo_id' => $ciclo->id],
            [
                'total_acumulado' => 0.00,
                'descripcion' => 'Fondo común del ciclo ' . $ciclo->nombre
            ]
        );

        return redirect()->route('ciclos.index')
            ->with('mensaje', 'Ciclo creado exitosamente.')
            ->with('icon', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ciclo $ciclo)
    {
        return view('ciclos.show', compact('ciclo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ciclo $ciclo)
    {
        return view('ciclos.edit', compact('ciclo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ciclo $ciclo)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'estado' => 'required|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $ciclo->update($request->all());

        // ==========================================
        // SEGURIDAD: Asegurar que si está activo tenga su fondo
        // ==========================================
        if ($ciclo->estado === 'ACTIVO') {
            CausaComun::firstOrCreate(
                ['ciclo_id' => $ciclo->id],
                [
                    'total_acumulado' => 0.00,
                    'descripcion' => 'Fondo común del ciclo ' . $ciclo->nombre
                ]
            );
        }

        return redirect()->route('ciclos.index')
            ->with('mensaje', 'Ciclo actualizado correctamente.')
            ->with('icon', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ciclo $ciclo)
    {
        $ciclo->delete(); // SoftDelete

        return redirect()->route('ciclos.index')
            ->with('mensaje', 'Ciclo enviado a la papelera correctamente.')
            ->with('icon', 'success');
    }

    /**
     * Restaurar un ciclo desde la papelera.
     */
    public function restore(int $id)
    {
        $ciclo = Ciclo::onlyTrashed()->findOrFail($id);
        $ciclo->restore();

        return redirect()->route('ciclos.trash')
            ->with('mensaje', 'Ciclo restaurado exitosamente.')
            ->with('icon', 'success');
    }
}
