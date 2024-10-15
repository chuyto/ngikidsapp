<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHijosAsistenciaToAsistenciasTable extends Migration
{
    public function up()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            // Agregamos una columna JSON para registrar la asistencia de los hijos
            $table->json('hijos_asistencia')->nullable();
        });
    }

    public function down()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('hijos_asistencia');
        });
    }
}

