<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Ciclo;
use App\Models\Team;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Ejecutar el seeder que define los roles y permisos
        $this->call([
            RoleSeeder::class,
            JuegosSeeder::class,
        ]);

        // 2. Limpiar caché de permisos de Spatie por seguridad
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 3. Buscar los roles creados
        $roleAdmin = Role::where('name', 'ADMINISTRADOR')->first();
        $roleSupervisor = Role::where('name', 'SUPERVISOR')->first();
        $roleEstudiante = Role::where('name', 'ESTUDIANTE')->first();
        // 4. Crear o buscar el usuario Administrador (RU: 00001)
        $admin = User::firstOrCreate(
            ['registro_universitario' => '00001'],
            [
                'name'              => 'admin',
                'alias'             => 'sudo',
                'email'             => 'admin@usalesiana.edu.bo',
                'password'          => Hash::make('12345'),
                'email_verified_at' => now(),
                'es_admin_raiz'     => true,
            ]
        );

        if (!$admin->es_admin_raiz) {
            $admin->update(['es_admin_raiz' => true]);
        }

        if ($roleAdmin && !$admin->hasRole('ADMINISTRADOR')) {
            $admin->assignRole($roleAdmin);
        }




        // 6. Crear o asegurar el Ciclo Activo (Periodo 2/2026) dentro de la función run()
        Ciclo::firstOrCreate(
            ['nombre' => 'Periodo 2/2026'],
            [
                'estado'       => 'ACTIVO',
                'fecha_inicio' => '2026-07-01 00:00:00',
                'fecha_fin'    => '2026-12-31 23:59:59',
            ]
        );

        // 8. Crear o asegurar un Team por defecto asociado al Ciclo Activo
        $cicloActivo = Ciclo::where('estado', 'ACTIVO')->first();

        if ($cicloActivo) {
            Team::firstOrCreate(
                ['nombre' => 'Equipo Teams'],
                [
                    'ciclo_id' => $cicloActivo->id,
                    'logo'     => null,
                ]
            );
        }
    } // <-- Aquí cierra correctamente el método run()
}
