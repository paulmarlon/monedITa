<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'es_admin_raiz')) {
                $table->boolean('es_admin_raiz')->default(false)->after('password');
            }
        });

        Schema::create('ciclo_usuario_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')
                ->constrained('ciclos')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('role_name', 40); // ADMINISTRADOR, SUPERVISOR, ESTUDIANTE
            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();
            $table->foreignId('asignado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['ciclo_id', 'user_id'], 'uq_ciclo_usuario_rol');
            $table->index(['ciclo_id', 'role_name'], 'idx_ciclo_rol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclo_usuario_roles');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'es_admin_raiz')) {
                $table->dropColumn('es_admin_raiz');
            }
        });
    }
};
