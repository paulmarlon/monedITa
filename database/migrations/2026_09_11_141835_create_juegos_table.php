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
        Schema::create('juegos', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // 'dinosaurio_runner', 'piedra_papel_tijera'
            $table->string('titulo'); // 'Dino Runner', etc.
            $table->text('descripcion')->nullable();
            $table->decimal('costo_ficha', 10, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juegos');
    }
};
