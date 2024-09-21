<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('asistencias', function (Blueprint $table) {
        $table->id();
        $table->string('uuid_short', 6); // Cambia padre_id a uuid_short
        $table->string('numero_ficha')->nullable();
        $table->time('hora_entrada');
        $table->time('hora_salida')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
