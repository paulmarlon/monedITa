<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name|max:255',
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Este rol ya existe en el sistema.',
        ]);

        Role::create(['name' => $request->name]);

        return redirect()->route('roles.index')
            ->with('mensaje', '¡Rol creado exitosamente!')
            ->with('icon', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Opcional si no usas vista de detalle
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role) // Usamos Route Model Binding para mayor limpieza
    {
        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|max:255|unique:roles,name,' . $role->id,
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Este nombre de rol ya está en uso.',
        ]);

        $role->update(['name' => $request->name]);

        return redirect()->route('roles.index')
            ->with('mensaje', '¡Rol actualizado correctamente!')
            ->with('icon', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Evitamos eliminar roles críticos por seguridad si lo deseas
        if ($role->name === 'Admin') {
            return redirect()->route('roles.index')
                ->with('mensaje', 'No se puede eliminar el rol de Administrador principal.')
                ->with('icon', 'error');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('mensaje', '¡Rol eliminado correctamente!')
            ->with('icon', 'success');
    }
    public function permissions(Role $role)
    {
        // Agrupamos los permisos por módulo (ej: "ciclos", "teams", "roles", "wallet")
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Si el nombre contiene un guion bajo, tomamos la segunda parte (ej: ver_ciclos -> ciclos)
            $parts = explode('_', $permission->name);
            return count($parts) > 1 ? $parts[1] : $parts[0];
        });

        return view('roles.permissions', compact('role', 'permissions'));
    }

    /**
     * Actualiza los permisos asignados a un rol.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        // Sincroniza los permisos seleccionados con el rol de Spatie
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with([
            'success' => 'Permisos actualizados correctamente para el rol ' . $role->name,
            'icon' => 'success'
        ]);
    }
}
