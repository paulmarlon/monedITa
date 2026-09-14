<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Creamos el esquema por si no existe en un entorno nuevo
        DB::statement('CREATE SCHEMA IF NOT EXISTS integrador;');

        // Creamos la tabla de sesiones de forma nativa asegurando el esquema
        DB::statement('
            CREATE TABLE IF NOT EXISTS integrador.sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id BIGINT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                payload TEXT NOT NULL,
                last_activity INT NOT NULL
            );
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS integrador.sessions;');
    }
};
