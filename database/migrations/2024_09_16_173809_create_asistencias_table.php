<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsistenciasTable extends Migration
{
    public function up()
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('padre_uuid'); // UUID del padre
            $table->string('numero_ficha')->nullable(); // Número de ficha
            $table->timestamps();

            $table->foreign('padre_uuid')->references('uuid')->on('padres')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asistencias');
    }
}
