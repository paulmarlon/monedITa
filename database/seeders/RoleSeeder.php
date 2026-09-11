<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Asegurar roles con firstOrCreate
        $admin = Role::firstOrCreate(['name' => 'ADMINISTRADOR']);
        $supervisor = Role::firstOrCreate(['name' => 'SUPERVISOR']);
        $estudiante = Role::firstOrCreate(['name' => 'ESTUDIANTE']);

        // 2. Lista completa de permisos del sistema
        $permissions = [
            'ver_configuracion',
            'editar_configuracion',
            'ver_ciclos',
            'crear_ciclos',
            'editar_ciclos',
            'eliminar_ciclos',
            'ver_teams',
            'crear_teams',
            'editar_teams',
            'eliminar_teams',
            'ver_roles',
            'crear_roles',
            'editar_roles',
            'eliminar_roles',
            'ver_wallet',
            'transferir_wallet',
            'acreditar_wallet',
            // --- NUEVOS PERMISOS DE JUEGOS ---
            'ver_juegos',
            'crear_juegos',
            'editar_juegos',
            'eliminar_juegos',
            // --- PERMISOS DE PUNTAJES / HISTORIAL ---
            'ver_puntajes',         // Para Admin/Supervisor (ver todos)
            //'eliminar_puntajes',    // Para Admin/Supervisor
            //'ver_mis_puntajes',
            'ver_dino',
        ];

        // Crear todos los permisos si no existen
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // 3. Asignar TODOS los permisos al ADMINISTRADOR
        $admin->syncPermissions($permissions);

        // 4. Asignar permisos específicos al SUPERVISOR (Gestión operativa y acreditación de tapitas)
        $supervisor->syncPermissions([
            'ver_ciclos',
            'ver_teams',
            'ver_wallet',
            'acreditar_wallet',
            // --- NUEVOS PERMISOS DE JUEGOS ---
            'ver_juegos',
            'ver_puntajes',         // Para Admin/Supervisor (ver todos)
            //'eliminar_puntajes',    // Para Admin/Supervisor
            //'ver_mis_puntajes',
            'ver_dino',
        ]);

        // 5. Asignar permisos específicos al ESTUDIANTE (Uso de su monedero y transferencias)
        $estudiante->syncPermissions([
            'ver_wallet',
            'transferir_wallet',
            // --- NUEVOS PERMISOS DE JUEGOS ---
            //'ver_juegos',
            'ver_puntajes',         // Para Admin/Supervisor (ver todos)
            //'eliminar_puntajes',    // Para Admin/Supervisor
            //'ver_mis_puntajes',
            'ver_dino',
        ]);
    }
}
