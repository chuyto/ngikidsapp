<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsistenciasTable extends Migration
{
    public function up()
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id(); // id es bigInteger por defecto
            $table->unsignedBigInteger('padre_id'); // Usar id de tipo bigInteger para la relación
            $table->string('numero_ficha')->nullable();
            $table->time('hora_entrada');
            $table->time('hora_salida')->nullable();
            $table->timestamps();

            // Definir la clave foránea
            $table->foreign('padre_id')->references('id')->on('padres')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asistencias');
    }
}
