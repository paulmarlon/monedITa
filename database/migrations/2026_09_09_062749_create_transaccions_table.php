<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaccions', function (Blueprint $table) {
            $table->id();

            // Relación con el ciclo académico
            $table->foreignId('ciclo_id')->constrained('ciclos')->onDelete('cascade');

            // Emisor y Receptor (Nullables porque el sistema o la causa común pueden emitir/recibir)
            $table->foreignId('emisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('receptor_id')->nullable()->constrained('users')->onDelete('set null');

            $table->decimal('monto', 10, 2);
            $table->string('tipo_operacion'); // "TRANSFERENCIA", "RECICLAJE", "CAUSA_DINOSAURIO", etc.
            $table->string('observacion')->nullable();

            $table->timestamp('created_at')->useCurrent(); // Solo necesitamos fecha de creación (Inmutable)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaccions');
    }
};
