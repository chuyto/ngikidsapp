<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServicioIdToAsistenciasTable extends Migration
{
    public function up()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->after('hijos_asistencia');
        });
    }

    public function down()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropColumn('servicio_id');
        });
    }
}
