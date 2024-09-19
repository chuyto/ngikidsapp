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
        Schema::create('padres', function (Blueprint $table) {
            $table->id(); // id es bigInteger por defecto
            $table->string('nombre');
            $table->string('red')->nullable();
            $table->string('telefono');
            $table->string('foto_padre')->nullable();
            $table->string('uuid_short', 6)->unique(); // Identificador adicional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('padres');
    }
};
