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
        Schema::table('padres', function (Blueprint $table) {
            // Agregamos el campo parentesco (Padre, Madre, Tutor, etc.)
            // Se coloca después de 'nombre' para mantener el orden visto en tus capturas
            $table->string('parentesco')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn('parentesco');
        });
    }
};
